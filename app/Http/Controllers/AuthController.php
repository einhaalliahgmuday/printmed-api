<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use App\Models\Otp;
use App\Models\ResetToken;
use App\Models\User;
use App\Notifications\AccountRestrictionNotification;
use App\Notifications\OtpVerificationNotification;
use App\Traits\CommonMethodsTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use CommonMethodsTrait;

    public function login(Request $request) 
    {
        $request->validate([
            'role' => 'required|string|in:admin,physician,secretary',   //super admin
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $query = User::query();
        if ($request->role == "admin") {
            $query->whereBlind('role', 'role_index', "super admin")->orWhereBlind('role', 'role_index', "admin");
        } else {
            $query->whereBlind('role', 'role_index', $request->role);
        }
        $user = $query->whereBlind('email', 'email_index', $request->email)->first();
                    

        //if account is not found or locked
        if (!$user || $user->is_locked) 
        {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        //if account is restricted due to failed login attempts
        if ($user->failed_login_attempts >= 3 && $user->failed_login_timestamp > Carbon::now()->subHour())
        {
            return response()->json([
                'message' => 'This account is temporarily restricted due to multiple failed login attempts. You may wait for an hour or contact the admin.'
            ], 403);
        }

        //if wrong password is provided
        if (!Hash::check($request->password, $user->password)) 
        {
            $user->failed_login_attempts++;
            $user->failed_login_timestamp = now();
            $user->save();

            // implemented custom audit event in when account is restricted due to 3 failed login attempts
            if ($user->failed_login_attempts >= 3)
            {
                $user->notify(new AccountRestrictionNotification($user->first_name));
                // event(new ModelAction(AuditAction::RESTRICT, $user, $user, null, $request));
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

        $otp = Otp::whereBlind('email', 'email_index', $request->email)->orderByDesc('expires_at')->first();
        $user = User::whereBlind('email', 'email_index', $request->email)->first();
        
        if (!$otp || !$user || ($otp && !Hash::check($request->token, $otp->token))) {
            return response()->json([
                'message' => 'Invalid request'
            ], 400);
        } else if (now()->isAfter($otp->expires_at)) {
            return response()->json([
                'message' => 'OTP is expired.'
            ], 410);
        } else if ($request->code != $otp->code) {
            return response()->json([
                'message' => 'Invalid OTP'
            ], 401);
        }
        
        $token = $user->createToken($user->id)->plainTextToken;
        $user->update(['email_verified_at' => now()]);
        $otp->delete();

        // audits login
        event(new ModelAction(AuditAction::LOGIN, $user, $user, null, $request));
        

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function resendOtp(Request $request) 
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email'
        ]);

        $otp = Otp::whereBlind('email', 'email_index', $request->email)->orderByDesc('expires_at')->first();
        $user = User::whereBlind('email', 'email_index', $request->email)->first();
        
        if (!$user || !$otp || ($otp && !Hash::check($request->token, $otp->token))) {
            return response()->json([
                'message' => 'Invalid request'
            ], 400);
        }
        
        $otp->update([
            'code' => rand(100000, 999999),
            'expires_at' => now()->addMinutes(5)
        ]);

        $user->notify(new OtpVerificationNotification($otp->code));
        

        return response()->json([
            'message' => "OTP resent successfully.",
        ], 200);
    }

    public function logout(Request $request) 
    {
        $request->user()->tokens()->delete();   

        // audits logout
        event(new ModelAction(AuditAction::LOGOUT, $request->user(), $request->user(), null, $request));

        return response()->json([
            'message' => 'You are logged out.'
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::whereBlind('email', 'email_index', $request->email)->where('is_locked', 0)->first();

        if (!$user) 
        {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $this->sendResetLink(false, $user);

        // implements audit of sending reset link, which is executed by Admin
        // event(new ModelAction(AuditAction::SENT_RESET_LINK, $request->user(), $user, null, $request));

        return response()->json([
            'message' => 'Reset link sent.'
        ], 200);
    }

    public function resetPassword(Request $request) 
    {
        $request->validate([
            // 'first_name' => 'required|string',
            // 'last_name' => 'required|string',
            'personnel_number' => 'required|string',
            'birthdate' => 'required|date|date_format:Y-m-d',
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/|confirmed'
        ]);

        $resetToken = ResetToken::orderByDesc('expires_at')->whereBlind('email', 'email_index', $request->email)->first();
        
        if (!$resetToken || !Hash::check($request->input('token'), $resetToken->token)) {
            return response()->json(['message'=> 'Invalid request'], 400);
        } else if ($resetToken && now()->isAfter($resetToken->expires_at)) {
            return response()->json(['message'=> 'Reset token is expired.'], 410);
        }

        $user = User::whereBlind('personnel_number', 'personnel_number_index', $request->personnel_number)
                    // ->whereBlind('last_name', 'last_name_index', $request->last_name)
                    ->whereBlind('birthdate', 'birthdate_index', $request->birthdate)
                    ->whereBlind('email', 'email_index', $request->email)->first();

        if (!$user) {
            return response()->json(['message'=> 'Invalid credentials'], 401);
        } else if ($user->is_locked) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }
    
        $user->forceFill(['password' => Hash::make($request->password), 'email_verified_at' => now()])->save();
        
        $resetToken->delete();
        $user->tokens()->delete();  // delete all login tokens
        
        // implements audit of resetting password
        // event(new ModelAction(AuditAction::RESET_PASSWORD, $user, $user, null, $request));
        
        return response()->json(['message' => 'Password has been reset successfully.'], 200);
    }
}
