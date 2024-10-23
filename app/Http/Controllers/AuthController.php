<?php

namespace App\Http\Controllers;

use App\AccountActionEnum;
use App\Events\AccountAction;
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

    public function sendResetPasswordEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users'
        ]);
        
        $isResetLinkSent = $this->sendResetLink($request->email);
        
        if (!$isResetLinkSent)
        {
            return response()->json([
                'message' => 'Reset link NOT sent.'
            ], 500);
        }

        $user = User::where('email', $request->email)->first();
        // implements audit of sending reset link, which is executed by Admin
        event(new AccountAction(AccountActionEnum::SENT_RESET_LINK, $request->user(), $user, $request));

        return response()->json([
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

        $user = User::select('id', 'personnel_number', 'first_name', 'last_name', 'birthdate')
                    ->where('personnel_number', $request->personnel_number)
                    ->where('first_name', $request->first_name)
                    ->where('last_name', $request->last_name)
                    ->where('birthdate', $request->birthdate)
                    ->where('email', $request->email)->first();

        if (!$user)
        {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 404);
        }

        $status = Password::reset($request->only('token', 'email', 'password', 'password_confirmation'),
            function ($user) use ($request) {
                $user->forceFill(['password' => Hash::make($request->password), 'email_verified_at' => now()])->save();

                //all access tokens will be deleted after password reset
                $user->tokens()->delete();

                event(new PasswordReset($user));
            });

        if ($status !== Password::PASSWORD_RESET) 
        {
            return response()->json(['message' => 'Failed to reset password.'], 400);
        }
        
        // implements audit of resetting password, which is executed by Admin
        event(new AccountAction(AccountActionEnum::RESET_PASSWORD, null, $user, $request));
        
        return response()->json(['message' => 'Password has been reset successfully.'], 200);
    }

    public function login(Request $request) 
    {
        $request->validate([
            'role' => 'required|string',
            'personnel_number' => 'required|string|size:8',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::select('id', 'email', 'password', 'failed_login_attempts', 'is_locked')
                    ->where('role', $request->role)
                    ->where('personnel_number', $request->personnel_number)
                    ->where('email', $request->email)
                    ->first();

        //if account is not found or locked
        if (!$user || $user->is_locked) 
        {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        //if account is restricted due to failed login attempts
        if ($user->failed_login_attempts >= 3)
        {
            return response()->json([
                'message' => 'This account is locked due to multiple failed login attempts. Please contact the admin.'
            ], 401);
        }

        //if wrong password is provided
        if (!Hash::check($request->password, $user->password)) 
        {
            $user->failed_login_attempts++;
            $user->save();

            // implemented custom audit event in when account is restricted due to 3 failed login attempts
            if ($user->failed_login_attempts >= 3)
            {
                event(new AccountAction(AccountActionEnum::RESTRICT, null, $user, $request));
            }

            return response()->json([
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

        $user = User::where('email', $request->email)->first();
        
        if (!$otp || now()->isAfter($otp->expires_at) || !Hash::check($request->token, $otp->token) || !$user) 
        {
            return response()->json([
                'message' => 'Invalid request'
            ], 404);
        }

        $otp->delete();
        $token = $user->createToken($user->email)->plainTextToken;

        // implements audit of login
        event(new AccountAction(AccountActionEnum::LOGIN, null, $user, $request));

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request) 
    {
        $request->user()->tokens()->delete();   

        // implements audit of logout
        event(new AccountAction(AccountActionEnum::LOGOUT, $request->user(), $request->user(), $request));

        return response()->json([
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
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.'
        ]);
    }
}
