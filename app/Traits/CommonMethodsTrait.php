<?php 

namespace App\Traits;

trait CommonMethodsTrait
{
    private function getFullName($firstName, $middleName, $lastName, $suffix): string
    {
        return $suffix ? trim("{$firstName} {$middleName} {$lastName}, {$suffix}") 
                    : trim("{$firstName} {$middleName} {$lastName}");
    }
}