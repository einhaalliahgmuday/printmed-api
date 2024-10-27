<?php

namespace App\Policies;

use App\Models\ConsultationRecord;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;

class ConsultationRecordPolicy
{
    public function view(User $user, ConsultationRecord $consultationRecord): bool
    {
        return $consultationRecord->physician_id === $user->id;
    }

    public function create(User $user, Request $request): bool
    {
        return $user->patients()->where('patients.id', $request->patient_id)->exists();
    }

    public function update(User $user, ConsultationRecord $consultationRecord): bool
    {
        return $consultationRecord->physician_id === $user->id;
    }
}
