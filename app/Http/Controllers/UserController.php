<?php

namespace App\Http\Controllers;

use App\AccountActionEnum;
use App\Events\AccountAction;
use App\Events\UpdateUser;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{

    // implemented custom audit event in: update information of user, lock toggle of account,
    // and unrestricting account from 3 failed login attempts
    // - which are all executed by the Admin

    public function index(Request $request)
    {
        $request->validate([
            'search' => 'string',
            'role' => 'string|in:admin,physician,secretary,queue manager',
            'department_id' => 'integer|exists:departments,id',
            'is_locked' => 'boolean',   //must only choose which to filter: is_locked or is_restricted
            'is_restricted' => 'boolean'
        ]);

        $query = User::query();

        if ($request->filled('search'))
        {
            $query->where(function($q) use ($request)
            {
                $q->where('personnel_number', 'LIKE', "%$request->search%")
                ->orWhere('first_name', 'LIKE', "%$request->search%")
                ->orWhere('middle_name', 'LIKE', "%$request->search%")
                ->orWhere('last_name', 'LIKE', "%$request->search%")
                ->orWhere('suffix', 'LIKE', "%$request->search%");
            });
        }

        if ($request->filled('role')) 
        {
            $query->where('role',$request->role);
        }

        if ($request->filled('department_id') && (!$request->filled('role') || in_array($request->role, ['physician', 'secretary']))) 
        {
            $query->where('department_id',$request->department_id);
        }

        if ($request->filled('is_locked'))
        {
            $query->where('is_locked',$request->is_locked);
        } else if ($request->filled('is_restricted'))
        {
            $request->is_restricted ? $query->where('failed_login_attempts', '>=', 3) : $query->where('failed_login_attempts', '<', 3);
        }

        $query->orderBy('updated_at');
        
        $users = $query->paginate(20);
        $users->appends($request->all());

        return $users;
    }

    public function getPhysicians(Request $request) 
    {
        $request->validate([
            'department_id' => 'integer|exists:departments,id'
        ]);

        // gets physicians whose accounts are not locked
        $query = User::query()->where('role','physician')->where('is_locked', false);

        if ($request->filled('department_id'))
        {
            $query->where('department_id',$request->department_id);
        }

        return $query->select('id', 'full_name', 'department')->get();
    }

    public function getUsersCount(Request $request)
    {
        $request->validate([
            'department_id' => 'integer|exists:departments,id'
        ]);

        //gets users count who are not locked
        $adminsCount = User::where('role', 'admin')->where('is_locked', false)->count();
        $physiciansCount = User::where('role', 'physician')->where('is_locked', false);
        $secretariesCount = User::where('role', 'secretary')->where('is_locked', false);
        $queueManagersCount = User::where('role', 'queue manager')->where('is_locked', false)->count();

        if ($request->filled('department_id'))
        {
            $physiciansCount->where('department_id', $request->department_id);
            $secretariesCount->where('department_id', $request->department_id);
        }

        $physiciansCount = $physiciansCount->count();
        $secretariesCount = $secretariesCount->count();
        
        return response()->json([
            'admins_count' => $adminsCount,
            'physicians_count' => $physiciansCount,
            'secretaries_count' => $secretariesCount,
            'queue_managers_count' => $queueManagersCount
        ]);
    }

    public function updateEmail(Request $request) 
    {
        $request->validate([
            'new_email' => 'required|email|unique:users,email|max:255',
        ]);

        $user = $request->user();

        $user->email = $request->new_email;
        $user->save();

        return $user;
    }

    public function updateInformation(Request $request, User $userToUpdate) 
    {
        $fields = $request->validate([
            'personnel_number' => 'string|size:8|unique:users',
            'first_name' => 'string|max:100',
            'middle_name' => 'string|max:100',
            'last_name' => 'string|max:100',
            'suffix' => 'string|max:20',
            'sex' => 'string|max:6',
            'birthdate' => 'date',
            'license_number' => 'string|max:50',
            'department_id' => 'integer|exists:departments,id',
            'email' => 'email|unique:users|max:255',
        ]);

        if (!in_array($userToUpdate->role, ['physician', 'secretary']))
        {
            $fields['license'] = null;
            $fields["department_id"] = null;
        }

        $originalData = $userToUpdate->getOriginal();

        $userToUpdate->update($fields);

        // implements audit of update
        event(new UpdateUser($request->user(), $originalData,$userToUpdate, $request));

        return $userToUpdate->getOriginal();
    }

    public function toggleLockUser(Request $request, User $userToUpdate)
    {
        $userToUpdate->is_locked = !$userToUpdate->is_locked;
        $userToUpdate->failed_login_attempts = 0;
        $userToUpdate->save();

        event(new AccountAction(AccountActionEnum::LOCK, $request->user(), $userToUpdate, $request));

        return $userToUpdate;
    }

    public function unrestrictAccount(Request $request, User $userToUpdate)
    {
        $userToUpdate->failed_login_attempts = 0;
        $userToUpdate->save();

        event(new AccountAction(AccountActionEnum::RESTRICT, $request->user(), $userToUpdate, $request));

        return $userToUpdate;
    }
}