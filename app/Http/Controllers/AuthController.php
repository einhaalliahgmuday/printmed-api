<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request) 
    {
        $fields = $request->validate([
            'role' => 'required|string|in:admin,physician,secretary,queue manager',
            'personnel_number' => 'required|string|size:8|unique:users',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'string|max:100',
            'last_name' => 'required|string|max:100',
            'suffix' => 'string|max:20',
            'sex' => 'string|required|max:6',
            'birthdate' => 'required|date',
            'department_id' => 'integer|exists:departments,id|required_if:role,physician|required_if:role,secretary',
            'license_number' => 'string|max:50',
            'email' => 'required|email|unique:users|max:255',
        ]);

        $user = User::create($fields);

        $isResetLinkSent = $this->sendResetLink($request->email);

        return response()->json([
            'is_reset_link_sent' => $isResetLinkSent,
            'user' => $user
        ]);
    }
    
    public function sendResetLink(string $email) 
    {
       $status = Password::sendResetLink(['email' => $email]);

        return $status === Password::RESET_LINK_SENT ? true : false;
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users'
        ]);
        
        $isResetLinkSent = $this->sendResetLink($request->email);
        
        if (!$isResetLinkSent)
        {
            return response()->json([
                'success' => false,
                'message' => 'Reset link NOT sent.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reset link sent.'
        ], 200);
    }

    public function resetPassword(Request $request) 
    {
        $request->validate([
            'personnel_number' => 'required|string|size:8',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'birthdate' => 'required|date',
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&]/|confirmed'
        ]);

        $user = User::select('personnel_number', 'first_name', 'last_name', 'birthdate')
                    ->where('email', $request->email)->first();

        if (!$user || ($request->personnel_number !== $user->personnel_number && $request->first_name !== $user->first_name 
            && $request->last_name !== $user->last_name && $request->birthdate !== $user->birthdate))
        {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        $status = Password::reset(
            $request->only('token', 'email', 'password', 'password_confirmation'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'email_verified_at' => now()
                ])->save();

                //all access tokens will be deleted after password reset
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) 
        {
            return response()->json(['status' => 'Failed to reset password.'], 400);
        }
        
        return response()->json(['status' => 'Password has been reset successfully.'], 200);
    }

    public function login(Request $request) 
    {
        $request->validate([
            'role' => 'required|string|exists:users',
            'personnel_number' => 'required|string|size:8|exists:users',
            'email' => 'required|email|exists:users',
            'password' => 'required',
        ]);

        $user = User::select('id', 'email', 'password', 'failed_login_attempts')
                    ->where('role', $request->role)
                    ->where('personnel_number', $request->personnel_number)
                    ->where('email', $request->email)
                    ->first();

        //if account is not found or locked
        if (!$user || ($user && ($user->is_locked === true))) 
        {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 403);
        }
        
        //if account is restricted due to failed login attempts
        if ($user->failed_login_attempts >= 3)
        {
            return response()->json([
                'success' => false,
                'message' => 'This account is locked due to multiple failed login attempts. Please contact the admin.'
            ], 401);
        }

        //if wrong password is provided
        if (!Hash::check($request->password, $user->password)) 
        {
            $user->failed_login_attempts++;
            $user->save();

            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        //sends otp to user's email as another authentication
        $token = Str::random(60);
        $otp = Otp::create([
            'token' => Hash::make($token),
            'email' => $request->email,
            'code' => rand(100000, 999999),
            'expires_at' => now()->addMinutes(5)
        ]);
        $user->notify(new OtpVerificationNotification($otp->code));

        //resets failed login attempts to 0
        $user->failed_login_attempts = 0;
        $user->save();

        return response()->json([
            'success'=> true,
            'email' => $request->email,
            'token' => $token
        ], 200);
    }

    public function verifyOtp(Request $request) 
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'code' => 'required|size:6'
        ]);

        $otp = Otp::where('email', $request->email)
            ->where('code', $request->code)
            ->first();
        
        //if otp credentials are incorrect
        if (!$otp || !Hash::check($request->token, $otp->token)) 
        {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request.'
            ], 400);
        }

        //if otp is expired
        if (now()->isAfter($otp->expires_at))
        {
            $otp->delete();
            
            return response()->json([
                'success' => false,
                'message' => 'OTP is expired.'
            ], 400);
        } 

        $user = User::where('email', $request->email)->first();
    
        //if user is not found
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $otp->delete();

        $token = $user->createToken($user->email)->plainTextToken;

        return response()->json([
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

    public function changePassword(Request $request) 
    {
        $user = $request->user();
        
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&]/|confirmed'
        ]);

        if (!Hash::check($request->current_password, $user->password))
        {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.'
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.'
        ]);
    }
}
