<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request) 
    {
        $request->validate([
            'role' => 'required|exists:users',
            'personnel_number' => 'required|string|size:8|exists:users',
            'password' => 'required',
        ]);

        $user = User::select('email', 'password')
                    ->where('role', strtolower($request->role))
                    ->where('personnel_number', $request->personnel_number)
                    ->first();

        if (!$user or !Hash::check($request->password, $user->password)) 
        {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        $token = Str::random(60);

        //sends otp to user's email as another authentication
        $otp = Otp::create([
            'token' => Hash::make($token),
            'email' => $user->email,
            'code' => rand(100000, 999999),
            'expires_at' => now()->addMinutes(5)
        ]);

        $user->notify(new OtpVerificationNotification($otp->code));

        return response()->json([
            'success'=> true,
            'email' => $user->email,
            'token' => $token
        ], 200);
    }

    public function verifyOtp(Request $request) 
    {
        $time = now();

        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:otps',
            'code' => 'required|size:6'
        ]);

        $otp = Otp::where('email', $request->email)
            ->where('code', $request->code)
            ->first();
        
        if (!$otp || !Hash::check($request->token, $otp->token)) 
        {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request.'
            ], 400);
        }

        if ($otp && $time->isAfter($otp->expires_at))
        {
            return response()->json([
                'success' => false,
                'message' => 'OTP is expired.'
            ], 400);
        } 

        $user = User::select('id', 'role', 'personnel_number', 
                            'first_name', 'middle_name', 'last_name', 'suffix', 
                            'department', 'license_number', 'email')
                    ->where('email', $request->email)
                    ->first();
        $otp->delete();
    
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $token = $user->createToken($user->email)->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token
        ]);
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
