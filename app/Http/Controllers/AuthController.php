<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use App\Models\Otp;
use App\Models\ResetToken;
use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use App\Notifications\ResetPasswordNotification;
use App\Traits\CommonMethodsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use CommonMethodsTrait;

    public function register(Request $request) 
    {
        $fields = $request->validate([
            'role' => 'required|string|in:admin,physician,secretary,queue manager',
            'personnel_number' => 'required|string|size:8',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'string|max:100',
            'last_name' => 'required|string|max:100',
            'suffix' => 'string|max:10',
            'sex' => 'required|string|max:6',
            'birthdate' => 'required|date|date_format:Y-m-d',
            'license_number' => 'string|max:50',
            'department_id' => 'integer|exists:departments,id',
            'email' => 'required|email|max:100',
        ]);

        if (in_array($request->role, ['physician', 'secretary'])) 
        {
            if (!$request->filled('department_id'))
            {
                return response()->json(['message' => 'The department field is required.'], 422);
            }
        }
        else {
            $fields['license'] = null;
            $fields["department_id"] = null;
        }

        if ($this->isUserPersonnelNumberExists($request->personnel_number)) 
        {
            return response()->json(['message' => 'The personnel number is already taken'], 422);
        }

        if ($this->isUserEmailExists($request->email)) 
        {
            return response()->json(['message' => 'The email is already taken.'], 422);
        }

        $fields['full_name'] = $this->getFullName($request->first_name, $request->last_name);

        $user = User::create($fields);

        // implements audit of user creation
        event(new ModelAction(AuditAction::CREATE, $request->user(), $user, null, $request));

        $this->sendResetLink(true, $user);

        return response()->json([
            'user' => $user
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::whereBlind('email', 'email_index', $request->email)->first();

        if (!$user) 
        {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $this->sendResetLink(false, $user);

        // implements audit of sending reset link, which is executed by Admin
        event(new ModelAction(AuditAction::SENT_RESET_LINK, $request->user(), $user, null, $request));

        return response()->json([
            'message' => 'Reset link sent.'
        ], 200);
    }

    public function resetPassword(Request $request) 
    {
        $request->validate([
            'personnel_number' => 'required|string',
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

        $user = User::whereBlind('personnel_number', 'personnel_number_index', $request->personnel_number)
                    ->whereBlind('first_name', 'first_name_index', $request->first_name)
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
        event(new ModelAction(AuditAction::RESET_PASSWORD, $user, null, null, $request));
        
        return response()->json(['message' => 'Password has been reset successfully.'], 200);
    }

    public function login(Request $request) 
    {
        $request->validate([
            'role' => 'required|string',
            'personnel_number' => 'required|string',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::whereBlind('role', 'role_index', $request->role)
                    ->whereBlind('personnel_number', 'personnel_number_index', $request->personnel_number)
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
                event(new ModelAction(AuditAction::RESTRICT, $user, null, null, $request));
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

        // implements audit of login
        event(new ModelAction(AuditAction::LOGIN, $user, null, null, $request));
        

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request) 
    {
        $request->user()->tokens()->delete();   

        // implements audit of logout
        event(new ModelAction(AuditAction::LOGOUT, $request->user(), null, null, $request));

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

    public function sendResetLink(bool $isNewAccount, User $user) 
    {
        $token = Str::random(60);

        ResetToken::create([
            'email' => $user->email,
            'token' => Hash::make($token),
            'expires_at' => now()->addHours(24)
        ]);
        
        $user->notify(new ResetPasswordNotification($isNewAccount, $token, $user->email));
    }
}
