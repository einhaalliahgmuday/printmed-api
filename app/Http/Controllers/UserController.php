<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use CommonMethodsTrait;

    public function register(Request $request) 
    {
        $fields = $request->validate([
            'role' => 'required|string|max:10|in:admin,physician,secretary,queue manager',
            'personnel_number' => 'required|string|size:8|unique:users',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'string|max:100',
            'last_name' => 'required|string|max:100',
            'suffix' => 'string|max:20',
            'department' => 'string|max:100|required_if:role,physician|required_if:role,secretary',
            'license_number' => 'string|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|string|min:8|max:255',
        ]);

        $fields['full_name'] = $this->getFullName($request);
        $user = User::create($fields);

        return $user;
    }

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

            if (in_array('role', ['physician', 'secretary']) && $request->filled('department')) 
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

        $physiciansCount->count();
        $secretariesCount->count();
        
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
            'email' => 'required|email|unique:users|max:255',
        ]);

        $user->email = $request->email;
        $user->save();

        return $user;
    }

    public function updateInformation(Request $request) 
    {
        $user = $request->user();
        
        $fields = $request->validate([
            'personnel_number' => 'string|size:8',
            'first_name' => 'string|max:100',
            'middle_name' => 'string|max:100',
            'last_name' => 'string|max:100',
            'suffix' => 'string|max:20',
            'department' => 'string|max:100',
            'license_number' => 'string|max:50',
            'email' => 'email|unique:users|max:255',
        ]);

        if ($user->role === 'admin') {
            $fields['full_name'] = $this->getFullName($request);
            $user->update($fields);

            return response()->json([
                'success' => true,
                'message' => 'User information successfully updated.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized Request'
        ], 401);
    }

    public function toggleLockUserAccount(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);

        $user->is_lock = !$user->is_lock;
        $user->save();

        return $user;
    }

    public function resetUserPassword(Request $request) 
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'new_password' => 'required|string'
        ]);

        $user = User::findOrFail('user_id');

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.'
        ]);
    }
}