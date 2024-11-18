<?php

namespace App\Http\Controllers;

use App\AccountActionEnum;
use App\AuditAction;
use App\Events\AccountAction;
use App\Events\ModelAction;
use App\Events\UpdateUser;
use App\Mail\VerifyEmailOtp;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use App\Traits\CommonMethodsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use CommonMethodsTrait;

    // implemented custom audit event in: update information of user, lock toggle of account,
    // and unrestricting account from 3 failed login attempts
    // - which are all executed by the Admin

    public function index(Request $request)
    {
        $request->validate([
            'search' => 'string',
            'role' => 'string|in:admin,physician,secretary,queue manager',
            'department_id' => 'integer|exists:departments,id',
            'status' => 'string|in:new,active,locked,restricted'
        ]);

        $query = User::query();

        if ($request->filled('search')) 
        {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                $q->whereBlind('personnel_number', 'personnel_number_index', $search)
                ->orWhereBlind('full_name', 'full_name_index', $search);
            });
        }
        
        if ($request->filled('role')) 
        {
            $query->whereBlind('role', 'role_index', $request->role);
        }

        if ($request->filled('department_id') && (!$request->filled('role') || in_array($request->role, ['physician', 'secretary']))) 
        {
            $query->where('department_id',$request->department_id);
        }

        if ($request->filled('status'))
        {
            switch($request->staus) {
                case 'new':
                    $query->whereColumn('created_at','updated_at');
                    break;
                case 'active':
                    $query->whereColumn('created_at','!=', 'updated_at');
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
        
        $users = $query->paginate(20);
        $users->appends($request->all());

        return $users;
    }

    public function show(User $user) {
        return $user;
    }

    public function getPhysicians(Request $request) 
    {
        $request->validate([
            'department_id' => 'integer|exists:departments,id'
        ]);

        // gets physicians whose accounts are not locked
        $query = User::query()->whereBlind('role','role_index', 'physician')->where('is_locked', false);

        if ($request->filled('department_id'))
        {
            $query->where('department_id', $request->department_id);
        }

        $physicians = $query->select('id', 'role', 'personnel_number', 'full_name', 'sex', 'department_id', 'license_number')->get();

        return $physicians;
    }

    public function getUsersCount(Request $request)
    {
        $request->validate([
            'department_id' => 'integer|exists:departments,id'
        ]);

        //gets users count who are not locked
        $adminsCount = User::whereBlind('role', 'role_index', 'admin')->where('is_locked', false)->count();
        $physiciansCount = User::whereBlind('role', 'role_index', 'physician')->where('is_locked', false);
        $secretariesCount = User::whereBlind('role', 'role_index', 'secretary')->where('is_locked', false);
        $queueManagersCount = User::whereBlind('role', 'role_index', 'queue manager')->where('is_locked', false)->count();

        if ($request->filled('department_id'))
        {
            $physiciansCount->where('department_id', $request->department_id);
            $secretariesCount->where('department_id', $request->department_id);
        }

        $physiciansCount = $physiciansCount->count();
        $secretariesCount = $secretariesCount->count();
        
        return response()->json([
            'admins' => $adminsCount,
            'physicians' => $physiciansCount,
            'secretaries' => $secretariesCount,
            'queue_managers' => $queueManagersCount
        ]);
    }

    public function updateEmail(Request $request) 
    {
        $request->validate([
            'new_email' => 'required|email|max:100',
        ]);

        if ($this->isUserEmailExists($request->new_email)) 
        {
            return response()->json(['message' => 'The email provided already exists.'], 422);
        }

        $user = $request->user();

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

    public function verifyEmailOtp(Request $request) 
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'code' => 'required|size:6'
        ]);

        $otp = Otp::whereBlind('email', 'email_index', $request->email)
            ->whereBlind('code', 'code_index', $request->code)
            ->first();

        $user = $otp->user;
        
        if (!$otp || now()->isAfter($otp->expires_at) || !Hash::check($request->token, $otp->token) || !$user) 
        {
            return response()->json([
                'message' => 'Invalid request'
            ], 404);
        }
        
        $user->update(['email' => $request->email, 'email_verified_at' => now()]);

        return $user;
    }

    public function updateInformation(Request $request, User $userToUpdate) 
    {
        $fields = $request->validate([
            'personnel_number' => 'string|size:8',
            'first_name' => 'string|max:100',
            'middle_name' => 'string|max:100',
            'last_name' => 'string|max:100',
            'suffix' => 'string|max:10',
            'sex' => 'string|max:6',
            'birthdate' => 'date|date_format:Y-m-d',
            'department_id' => 'integer|exists:departments,id',
            'email' => 'email|max:100',
        ]);

        if ($request->email !== $userToUpdate->email && $this->isUserEmailExists($request->email)) 
        {
            return response()->json(['message' => 'The email provided already exists.'], 422);
        }

        if ($request->personnel_number !== $userToUpdate->personnel_number && $this->isUserPersonnelNumberExists($request->personnel_number)) 
        {
            return response()->json(['message' => 'The personnel number provided already exists.'], 422);
        }

        $originalData = $userToUpdate->toArray();

        if (!in_array($userToUpdate->role, ['physician', 'secretary']))
        {
            $fields['license'] = null;
            $fields["department_id"] = null;
        }

        $userToUpdate->update($fields);

        if ($request->filled('first_name') || $request->filled('last_name'))
        {
            $userToUpdate->update(['full_name' => $this->getFullName($userToUpdate->first_name, $userToUpdate->last_name)]);
        }

        // implements audit of update
        event(new ModelAction(AuditAction::UPDATE, $request->user(), $userToUpdate, $originalData, $request));

        return $userToUpdate;
    }

    public function toggleLock(Request $request, User $userToUpdate)
    {
        $userToUpdate->is_locked = !$userToUpdate->is_locked;
        $userToUpdate->failed_login_attempts = 0;
        $userToUpdate->save();

        // if account is locked, all its access tokens will be deleted
        if($userToUpdate->is_locked)
        {
            $userToUpdate->tokens()->delete();
        }

        // audit locking of account
        event(new ModelAction(AuditAction::LOCK, $request->user(), $userToUpdate, null, $request));

        return $userToUpdate;
    }

    public function unrestrict(Request $request, User $userToUpdate)
    {
        $failedLoginAttempts = $userToUpdate->failed_login_attempts;
        
        $userToUpdate->failed_login_attempts = 0;
        $userToUpdate->save();

        $failedLoginAttempts <= 3 ?: event(new ModelAction(AuditAction::UNRESTRICT, $request->user(), $userToUpdate, null, $request));

        return $userToUpdate;
    }

    public function isEmailExists(Request $request)
    {
        $request->validate([
            'email' => 'email|max:100'
        ]);

        return $this->isUserEmailExists($request->email);
    }

    public function isPersonnelNumberExists(Request $request)
    {
        $request->validate([
            'personnel_number' => 'string|max:8'
        ]);
        
        return $this->isUserPersonnelNumberExists($request->personnel_number);
    }
}