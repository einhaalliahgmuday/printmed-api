<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function updateEmail(Request $request) 
    {
        $user = $request->user();

        $request->validate([
            'email' => 'email|unique:users|max:255',
        ]);

        $user->update($$request->email);

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

        if ($user->role == 'admin') {
            $user->update($fields);

            return response()->json([
                'success' => true,
                'message' => 'User information successfully updated'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized Request'
        ], 401);
    }
}