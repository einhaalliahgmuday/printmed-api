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
            'role' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'string|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|string|min:8|max:255',
        ]);

        $user = User::create($fields);

        return $user;
    }

    public function login(Request $request) 
    {
        $request->validate([
            'role' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('role', strtolower($request->role))
                    ->where('email', $request->email)
                    ->first();

        if (!$user or !Hash::check($request->password, $user->password)) 
        {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.'
            ], 400);
        }

        $token = $user->createToken($user->email);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function logout(Request $request) 
    {
        $request->user()->tokens()->delete();       //can only use $request->user when authenticated

        return response()->json([
            'success' => true,
            'message' => 'You are logged out.'
        ], 200);
    }
}
