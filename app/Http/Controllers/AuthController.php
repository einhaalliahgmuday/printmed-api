<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use App\Models\Otp;
use App\Models\ResetToken;
use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use App\Traits\CommonMethodsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use CommonMethodsTrait;

    public function login(Request $request) 
    {
        $request->validate([
            'role' => 'required|string',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::whereBlind('role', 'role_index', $request->role)
                    ->whereBlind('email', 'email_index', $request->email)
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
                'message' => 'This account is restricted due to multiple failed login attempts. Please contact the admin.'
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
                event(new ModelAction(AuditAction::RESTRICT, $user, $user, null, $request));
            }

            return response()->json([
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        //sends otp to user's email as another authentication
        $token = Str::random(60);
        $otp = Otp::create([
            'email' => $request->email,
            'code' => rand(100000, 999999),
            'token' => Hash::make($token),
            'expires_at' => now()->addMinutes(5)
        ]);

        $user->notify(new OtpVerificationNotification($otp->code));

        $user->update(['failed_login_attempts' => 0]);

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

        $otp = Otp::whereBlind('email', 'email_index', $request->email)
            ->whereBlind('code', 'code_index', $request->code)
            ->first();

        $user = User::whereBlind('email', 'email_index', $request->email)->first();
        
        if (!$otp || now()->isAfter($otp->expires_at) || !Hash::check($request->token, $otp->token) || !$user) 
        {
            return response()->json([
                'message' => 'Invalid request'
            ], 404);
        }
        
        $token = $user->createToken($user->id)->plainTextToken;
        $user->update(['email_verified_at' => now()]);

        // implements audit of login
        event(new ModelAction(AuditAction::LOGIN, $user, $user, null, $request));
        

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request) 
    {
        $request->user()->tokens()->delete();   

        // implements audit of logout
        event(new ModelAction(AuditAction::LOGOUT, $request->user(), $request->user(), null, $request));

        return response()->json([
            'message' => 'You are logged out.'
        ], 200);
    }

    public function resetPassword(Request $request) 
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'birthdate' => 'required|date|date_format:Y-m-d',
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&]/|confirmed'
        ]);

        $resetToken = ResetToken::orderByDesc('expires_at')->whereBlind('email', 'email_index', $request->email)->first();
        
        if (!$resetToken || !Hash::check($request->input('token'), $resetToken->token) || now()->isAfter($resetToken->expires_at))
        {
            return response()->json(['message'=> 'Invalid request'], 400);
        }

        $user = User::whereBlind('first_name', 'first_name_index', $request->first_name)
                    ->whereBlind('last_name', 'last_name_index', $request->last_name)
                    ->whereBlind('birthdate', 'birthdate_index', $request->birthdate)
                    ->whereBlind('email', 'email_index', $request->email)->first();

        if (!$user)
        {
            return response()->json(['message'=> 'Invalid credentials'], 404);
        }
    
        
        $user->forceFill(['password' => Hash::make($request->password), 'email_verified_at' => now()])->save();
        
        $resetToken->delete();
        $user->tokens()->delete();  // delete all login tokens
        
        // implements audit of resetting password
        event(new ModelAction(AuditAction::RESET_PASSWORD, $user, $user, null, $request));
        
        return response()->json(['message' => 'Password has been reset successfully.'], 200);
    }
}
