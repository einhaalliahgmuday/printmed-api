<?php 

namespace App\Traits;

use App\Models\ResetToken;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait CommonMethodsTrait
{
    private function getFullName($firstName, $lastName): string
    {
        return "{$firstName} {$lastName}";
    }

    public function isUserEmailExists(string $email)
    {
        return User::whereBlind('email', 'email_index', $email)->exists();
    }

    public function isUserPersonnelNumberExists(string $personnelNumber)
    {
        return User::whereBlind('personnel_number', 'personnel_number_index', $personnelNumber)->exists();
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