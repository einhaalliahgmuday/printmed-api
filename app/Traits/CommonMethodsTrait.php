<?php 

namespace App\Traits;

trait CommonMethodsTrait
{
    private function getFullName($firstName, $middleName, $lastName, $suffix): string
    {
        // return $suffix ? trim("{$firstName} {$middleName} {$lastName}, {$suffix}") 
        //             : ( $middleName ? trim("{$firstName} {$middleName} {$lastName}")
        //                             : trim("{$firstName} {$lastName}"));

        return $suffix ? trim("{$firstName} {$lastName}, {$suffix}") 
                    : trim("{$firstName} {$lastName}");
    }
}