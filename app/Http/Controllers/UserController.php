<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use CommonMethodsTrait;

    public function getUsers(Request $request)
    {
        $request->validate([
            'role' => 'string|in:admin,physician,secretary,queue manager',
            'department' => 'string|max:100'
        ]);

        $query = User::query();

        if ($request->filled('role')) 
        {
            $query->where('role',$request->role);

            if (in_array($request->role, ['physician', 'secretary']) && $request->filled('department')) 
            {
                $query->where('department',$request->department);
            }
        }

        $query->orderBy('updated_at');
        
        $users = $query->select('id', 'role', 'personnel_number', 'full_name', 'department', 'license_number', 'email')->paginate(20);
        $users->appends($request->all());

        return $users;
    }

    public function getPhysicians() 
    {
        return User::select('id', 'full_name', 'department')->where('role','physician')->get();
    }

    public function getUsersCount(Request $request)
    {
        $request->validate([
            'department' => 'string|max:100'
        ]);

        $adminsCount = User::where('role', 'admin')->count();
        $physiciansCount = User::where('role', 'physician');
        $secretariesCount = User::where('role', 'secretary');
        $queueManagersCount = User::where('role', 'queue manager')->count();

        if ($request->filled('department'))
        {
            $physiciansCount->where('department', $request->department);
            $secretariesCount->where('department', $request->department);
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
        $user = $request->user();

        $request->validate([
            'new_email' => 'required|email|unique:users,email|max:255',
        ]);

        $user->email = $request->new_email;
        $user->save();

        return $user;
    }

    public function updateInformation(Request $request, User $userToUpdate) 
    {
        $fields = $request->validate([
            // 'user_id' => 'required|integer|exists:users,id',
            'personnel_number' => 'string|size:8',
            'first_name' => 'string|max:100',
            'middle_name' => 'string|max:100',
            'last_name' => 'string|max:100',
            'suffix' => 'string|max:20',
            'sex' => 'string|max:6',
            'birthdate' => 'date',
            'license_number' => 'string|max:50',
            'department' => 'string|max:100',
            'email' => 'email|unique:users|max:255',
        ]);

        // $userToUpdate = User::findOrFail($request->user_id);

        if (!$userToUpdate)
        {
            return response()->json([
                'success' => true,
                'message' => 'User not found.'
            ], 404);   
        }

        if (!in_array($userToUpdate->role, ['physician', 'secretary']))
        {
            $fields['license'] = "";
            $fields["department"] = "";
        }

        $userToUpdate->update($fields);

        return response()->json([
            'success' => true,
            'user' => $userToUpdate,
            'message' => 'User information successfully updated.'
        ], 200);
    }

    public function toggleLockUserAccount(User $userToUpdate)
    {
        // $request->validate([
        //     'user_id' => 'required|integer|exists:users,id'
        // ]);

        // $user = User::findOrFail($request->user_id);

        $userToUpdate->is_locked = !$userToUpdate->is_locked;
        $userToUpdate->save();

        return $userToUpdate;
    }

    public function unrestrictAccount(User $userToUpdate)
    {
        // $request->validate([
        //     'user_id' => 'required|integer|exists:users,id'
        // ]);

        // $user = User::findOrFail($request->user_id);

        $userToUpdate->failed_login_attempts = 0;
        $userToUpdate->save();

        return $userToUpdate;
    }
}