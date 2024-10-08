<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;

class PasswordController extends Controller
{
    
    public function changePassword(Request $request) 
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|confirmed'
        ]);

        $user = $request->user();

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

    public function sendResetLink(Request $request) 
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $status = Password::sendResetLink(
            $request->only('email'),
            // function ($user, $token) {
            //     $url = url("http://127.0.0.1/reset-password/?token={$token}&email={$user->email}");

            //     Mail::to($user->email)->send(new ResetPasswordMail($url));
            // }
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['status' => _($status)], 200)
            : response()->json(['status' => _($status)], 400);
    }
    
    public function resetPassword(Request $request) 
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed'
        ]);

        $status = Password::reset(
            $request->only('token', 'email', 'password', 'password_confirmation'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password)
                ]);

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['status' => _($status)], 200)
            : response()->json(['status' => _($status)], 400);
    }
}
