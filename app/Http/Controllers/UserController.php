<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use App\Mail\VerifyEmailOtp;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\AccountLockedNotification;
use App\Traits\CommonMethodsTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use CommonMethodsTrait;


    // ADMIN ACTIONS

    public function index(Request $request)
    {
        $request->validate([
            'page' => 'integer',
            'search' => 'string',   // personnel number, full name, first name, last name
            'role' => 'string|in:admin,physician,secretary',
            'department_id' => 'integer|exists:departments,id',
            'status' => 'string|in:new,active,locked,restricted',
            'sort_by' => 'string|in:personnel_number',
            'order_by' => 'string|in:asc,desc'
        ]);

        $user = $request->user();

        $query = User::query();

        if ($user->role == "admin") {
            if ($request->filled('role') && in_array($request->role, ['physician', 'secretary'])) {
                $query->whereBlind('role', 'role_index', $request->role);
            } else {
                $query->where(function($q) {
                    $q->whereBlind('role', 'role_index', 'secretary')
                    ->orWhereBlind('role', 'role_index', "physician");
                });
            }
        } else {
            if ($request->filled('role')) {
                $query->whereBlind('role', 'role_index', $request->role);
            }
        }

        if ($request->filled('search')) 
        {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                $q->whereBlind('personnel_number', 'personnel_number_index', $search)
                ->orWhereBlind('full_name', 'full_name_index', $search)
                ->orWhereBlind('first_name', 'first_name_index', $search)
                ->orWhereBlind('last_name', 'last_name_index', $search);
            });
        }


        if ($request->filled('department_id') && (!$request->filled('role') || in_array($request->role, ['physician', 'secretary']))) 
        {
            $query->where('department_id',$request->department_id);
        }

        if ($request->filled('status'))
        {
            switch($request->status) {
                case 'new':
                    $query->where('email_verified_at', null)->where('is_locked', 0);
                    break;
                case 'active':
                    $query->where('email_verified_at', '!=', null)->where('is_locked', 0, )->where('failed_login_attempts', '<=', 2);
                    break;
                case 'locked':
                    $query->where('is_locked', 1);
                    break;
                case 'restricted':
                    $query->where('failed_login_attempts', '>', 2);
                    break;
            }
        }

        $query->orderBy('updated_at', 'desc');
        $users = $query->get();

        if (count($users) > 0)
        {
            if($request->filled('sort_by')) 
            {
                $isDesc = $request->input('order_by') === 'desc';

                $users = $users->sortBy("personnel_number", SORT_REGULAR, $isDesc)->values();
            }

            $page = $request->input('page',1);
            $data = array_slice($users->toArray(), ($page - 1) * 15, 15);
            $paginator = new LengthAwarePaginator(
                $data, 
                count($users), 
                15, 
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $paginator->appends($request->query());

            return $paginator;
        }

        return response()->json(['data' => null]);
    }

    public function store(Request $request) 
    {
        $fields = $request->validate([
            'role' => 'required|string|in:admin,physician,secretary',
            'personnel_number' => 'required|string|size:10',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'string|max:100',
            'last_name' => 'required|string|max:100',
            'suffix' => 'string|max:10',
            'sex' => 'required|string|max:6',
            'birthdate' => 'required|date|date_format:Y-m-d',
            'department_id' => 'integer|exists:departments,id',
            'email' => 'required|email|max:100',
        ]);

        // Gate::authorize('is-authorized-admin-action', [$request->role]);

        // $user = $request->user();

        // // cannot create admin account if not super admin
        // if ($user->role != "super admin" && $request->role == "admin") {
        //     return response()->json(['message' => 'Unauthorized to create admin account.'], 401);
        // }

        if (in_array($request->role, ['physician', 'secretary'])) 
        {
            if (!$request->filled('department_id'))
            {
                return response()->json(['message' => 'The department field is required.'], 422);
            }
        }
        else {
            $fields["department_id"] = null;
        }

        if ($this->isUserPersonnelNumberExists($request->personnel_number)) 
        {
            return response()->json(['field' => 'personnel_number', 'message' => 'The personnel number is already taken'], 422);
        }

        if ($this->isUserEmailExists($request->email)) 
        {
            return response()->json(['field' => 'email', 'message' => 'The email is already taken.'], 422);
        }

        $fields['full_name'] = "{$request->first_name} {$request->last_name}";

        $user = User::create($fields);

        // implements audit of user creation
        event(new ModelAction(AuditAction::CREATE, $request->user(), $user, null, $request));

        $this->sendResetLink(true, $user);

        return $user;
    }

    public function isEmailExists(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:100'
        ]);

        return $this->isUserEmailExists($request->email);
    }

    public function isPersonnelNumberExists(Request $request)
    {
        $request->validate([
            'personnel_number' => 'required|string|max:8'
        ]);
        
        return $this->isUserPersonnelNumberExists($request->personnel_number);
    }

    public function show(Request $request, User $user) {
        // cannot view admin account if not super admin
        // if ($request->user()->role == "admin" && ($user->role == "admin" || $user->role == "super admin")) {
        //     return response()->json(['message' => 'Unauthorized to view this account.'], 401);
        // }

        // Gate::authorize('is-authorized-admin-action', [$user->role]);

        return $user;
    }

    public function updateInformation(Request $request, User $userToUpdate) 
    {
        $fields = $request->validate([
            'role' => 'string|in:admin,physician,secretary',
            'personnel_number' => 'string|size:10',
            'first_name' => 'string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'string|max:100',
            'suffix' => 'nullable|string|max:10',
            'sex' => 'string|max:6',
            'birthdate' => 'date|date_format:Y-m-d',
            'department_id' => 'nullable|integer|exists:departments,id',
            'email' => 'email|max:100',
        ]);

        $user = $request->user();

        // // cannot update admin account if not super admin
        // if ($user->role != "super admin" && ($userToUpdate->role == "admin" || $userToUpdate->role == "super admin")) {
        //     return response()->json(['message' => 'Unauthorized'], 401);
        // }

        // Gate::authorize('is-authorized-admin-action', [$userToUpdate->role]);

        // if ($request->filled('role'))
        // {
        //     Gate::authorize('is-authorized-admin-action', [$request->role]);
        // }

        if ($request->filled('email') && $request->email !== $userToUpdate->email && $this->isUserEmailExists($request->email)) 
        {
            return response()->json(['field' => 'email', 'message' => 'The email is already taken.'], 422);
        }

        if ($request->filled('personnel_number') && $request->personnel_number !== $userToUpdate->personnel_number && $this->isUserPersonnelNumberExists($request->personnel_number)) 
        {
            return response()->json(['field' => 'personnel_number', 'message' => 'The personnel number is already taken'], 422);
        }

        if ($request->filled('role') && in_array($request->role, ['physician', 'secretary'])) 
        {
            if (!$request->filled('department_id') || $request->department_id == "")
            {
                return response()->json(['message' => 'The department field is required.'], 422);
            }
        }

        $originalData = $userToUpdate->toArray();

        if ($request->filled('role') && !in_array($request->role, ['physician', 'secretary']))
        {
            $fields["department_id"] = null;
        }

        $userToUpdate->update($fields);

        if ($request->filled('first_name') || $request->filled('last_name'))
        {
            $userToUpdate->update(['full_name' => "{$request->first_name} {$request->last_name}"]);
        }

        // implements audit of update
        event(new ModelAction(AuditAction::UPDATE, $user, $userToUpdate, $originalData, $request));

        return $userToUpdate;
    }

    public function toggleLock(Request $request, User $userToUpdate)
    {
        $user = $request->user();

        // // cannot update admin account if not super admin
        // if ($user->role != "super admin" && ($userToUpdate->role == "admin" || $userToUpdate->role == "super admin")) {
        //     return response()->json(['message' => 'Unauthorized'], 401);
        // }

        // Gate::authorize('is-authorized-admin-action', [$userToUpdate->role]);

        $userToUpdate->is_locked = !$userToUpdate->is_locked;
        $userToUpdate->failed_login_attempts = 0;
        $userToUpdate->save();

        // if account is locked, all its access tokens will be deleted
        if($userToUpdate->is_locked)
        {
            $userToUpdate->tokens()->delete();
            $userToUpdate->notify(new AccountLockedNotification($userToUpdate->first_name));
        }

        // audit locking of account
        event(new ModelAction(AuditAction::LOCK, $user, $userToUpdate, null, $request));

        return $userToUpdate;
    }

    public function unrestrict(Request $request, User $userToUpdate)
    {
        // $user = $request->user();

        // // cannot unrestrict admin account if not super admin
        // if ($user->role != "super admin" && ($userToUpdate->role == "admin" || $userToUpdate->role == "super admin")) {
        //     return response()->json(['message' => 'Unauthorized'], 401);
        // }

        // Gate::authorize('is-authorized-admin-action', [$userToUpdate->role]);

        // $failedLoginAttempts = $userToUpdate->failed_login_attempts;
        
        $userToUpdate->failed_login_attempts = 0;
        $userToUpdate->save();

        // $failedLoginAttempts <= 3 ?: event(new ModelAction(AuditAction::UNRESTRICT, $request->user(), $userToUpdate, null, $request));

        return $userToUpdate;
    }

    public function count(Request $request)
    {
        $user = $request->user();

        $physicians = User::whereBlind('role', 'role_index', 'physician')->count();
        $secretaries = User::whereBlind('role', 'role_index', 'secretary')->count();

        if ($user->role == "super admin") {
            return response()->json([
                'admins' => User::whereBlind('role', 'role_index', 'admin')->count(),
                'physicians' => $physicians,
                'secretaries' => $secretaries
            ]);
        }
        
        return response()->json([
            'physicians' => $physicians,
            'secretaries' => $secretaries
        ]);
    }



    // USER ACTIONS

    public function updateEmail(Request $request) 
    {
        $request->validate([
            'new_email' => 'required|email|max:100',
        ]);

        $user = $request->user();

        if ($this->isUserEmailExists($request->new_email)) 
        {
            return response()->json(['message' => 'The email is already taken.'], 422);
        }

        //sends otp to user's email as another authentication
        $token = Str::random(60);
        $otp = Otp::create([
            'email' => $request->new_email,
            'code' => rand(100000, 999999),
            'token' => Hash::make($token),
            'expires_at' => now()->addMinutes(5),
            'user_id' => $user->id
        ]);

        Mail::to($request->new_email)->send(new VerifyEmailOtp($otp->code));

        return response()->json([
            'email' => $request->new_email,
            'token' => $token,
        ], 200);
    }

    public function resendUpdateEmailOtp(Request $request) 
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email'
        ]);

        $otp = Otp::whereBlind('email', 'email_index', $request->email)->orderByDesc('expires_at')->first();
        
        if (!$otp || ($otp && !Hash::check($request->token, $otp->token))) {
            return response()->json([
                'message' => 'Invalid request'
            ], 400);
        }
        
        $otp->update([
            'code' => rand(100000, 999999),
            'expires_at' => now()->addMinutes(5)
        ]);

        Mail::to($request->email)->send(new VerifyEmailOtp($otp->code));

        return response()->json([
            'message' => "OTP resent successfully.",
        ], 200);
    }

    public function verifyUpdateEmailOtp(Request $request) 
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'code' => 'required|size:6'
        ]);

        $otp = Otp::whereBlind('email', 'email_index', $request->email)
            ->orderByDesc('expires_at')
            ->first();

        $user = $otp->user;
        
        if (!$otp || !Hash::check($request->token, $otp->token) || !$user) {
            return response()->json([
                'message' => 'Invalid request'
            ], 400);
        } else if (now()->isAfter($otp->expires_at)) {
            return response()->json([
                'message' => 'OTP is expired.'
            ], 410);
        } else if ($request->code != $otp->code) {
            return response()->json([
                'message' => 'OTP is invalid.'
            ], 401);
        }
        
        $user->update(['email' => $request->email, 'email_verified_at' => now()]);

        return $user;
    }

    public function changePassword(Request $request) 
    {
        $user = $request->user();
        
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/|confirmed'
        ]);

        if (!Hash::check($request->current_password, $user->password))
        {
            return response()->json([
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.'
        ]);
    }

    public function uploadSignature(Request $request) {
        $user = $request->user();
        if ($user->role != "physician") {
            return response()->json([
                'message' => "Unauthorized"
            ], 403);
        }

        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        if ($user->signature != null && $user->signature != "") {
            Storage::delete($user->signature);
        }
        $path = $request->file('signature')->store('images/signatures', ['local', 'private']);
        $user->update(['signature' => $path]);

        return response()->json([
            'signature' => Storage::temporaryUrl($user->signature, now()->addHours(16))
        ], 200);
    }

    public function deleteSignature(Request $request) {
        $user = $request->user();
        if ($user->role != "physician") {
            return response()->json([
                'message' => "Unauthorized"
            ], 403);
        }

        if ($user->signature != null && $user->signature != "") {
            Storage::delete($user->signature);
            $user->update(['signature' => '']);
        }

        return response()->json(['message' => 'Signature deleted successfully.'], 200);
    }

    public function getPhysicians(Request $request) 
    {
        $user = $request->user();

        // gets physicians from the same department as secretary and whose accounts are not locked
        $query = User::query()->select('id', 'full_name', 'first_name', 'middle_name', 'last_name', 'suffix', 'department_id')
                    ->whereBlind('role', 'role_index', 'physician')->where('department_id', $user->department_id)->where('is_locked', false);

        $physicians = $query->get()->makeHidden('email');

        return $physicians;
    }
}