<?php 

namespace App\Traits;

use App\Models\User;

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
}