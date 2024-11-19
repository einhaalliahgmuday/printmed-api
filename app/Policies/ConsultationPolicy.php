<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\PatientPhysician;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;

class ConsultationPolicy
{
    public function get(User $user, Patient $patient): bool
    {
        return $user->patients()->where('patients.id', $patient->id)->exists();
    }

    public function create(Request $request): bool
    {
        return $request->user()->patients()->where('patients.id', $request->patient_id)->exists();
    }
    
    public function view(User $user, Consultation $consultation): bool
    {
        return $consultation->department_id === $user->department_id;
    }

    public function update(User $user, Consultation $consultation): bool
    {
        return $consultation->physician_id === $user->id;
    }
}
