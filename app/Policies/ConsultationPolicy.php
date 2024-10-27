<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;

class ConsultationPolicy
{
    public function view(User $user, Consultation $consultation): bool
    {
        return $consultation->physician_id === $user->id;
    }

    public function create(User $user, Request $request): bool
    {
        return $user->patients()->where('patients.id', $request->patient_id)->exists();
    }

    public function update(User $user, Consultation $consultation): bool
    {
        return $consultation->physician_id === $user->id;
    }
}
