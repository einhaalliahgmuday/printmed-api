<?php

use App\Models\User;
use App\Models\VitalSigns;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('registration', function (User $user) {
    return $user->role == "secretary";
});

Broadcast::channel('audit', function (User $user) {
    return $user->role == "admin";
});

Broadcast::channel('vital-signs.{patientId}', function (User $user, $patientId) {
    return $user->role == "physician" && $user->patients()->where('patients.id', $patientId)->exists();
});
