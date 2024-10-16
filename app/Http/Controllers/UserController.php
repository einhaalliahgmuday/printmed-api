<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use CommonMethodsTrait;

    public function register(Request $request) 
    {
        $fields = $request->validate([
            'role' => 'required|string|max:10|in:admin,physician,secretary,queue_manager',
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

    public function getPhysicians() 
    {
        return User::select('id', 'full_name', 'department')->where('role','physician')->get();
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
}