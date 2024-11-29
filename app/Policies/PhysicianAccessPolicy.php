<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

class PhysicianAccessPolicy
{
    public function isAssignedPhysician(User $user, $patientId): bool
    {
        return $user->patients()->where('patients.id', $patientId)->exists();
    }
}
