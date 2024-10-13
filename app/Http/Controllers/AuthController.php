<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request) 
    {
        $fields = $request->validate([
            'role' => 'required|string|max:10',
            'personnel_number' => 'required|string|size:8|exists:users',
            // 'username' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'string|max:100',
            'last_name' => 'required|string|max:100',
            'suffix' => 'string|max:20',
            'specialization' => 'string|max:100',
            'department' => 'string|max:100',
            'license_number' => 'string|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|string|min:8|max:255',
        ]);

        $user = User::create($fields);

        return $user;
    }

    public function login(Request $request) 
    {
        $request->validate([
            'role' => 'required|exists:users',
            'personnel_number' => 'required|string|size:8|exists:users',
            'password' => 'required',
        ]);

        $user = User::where('role', strtolower($request->role))
                    ->where('personnel_number', $request->personnel_number)
                    ->first();

        if (!$user or !Hash::check($request->password, $user->password)) 
        {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.'
            ], 404);
        }

        $token = $user->createToken($user->email);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function logout(Request $request) 
    {
        $request->user()->tokens()->delete();   

        return response()->json([
            'success' => true,
            'message' => 'You are logged out.'
        ], 200);
    }
}
