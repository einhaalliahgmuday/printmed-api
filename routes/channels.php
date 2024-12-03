<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('registration', function (User $user) {
    return $user->role == "secretary";
});

Broadcast::channel('audit', function (User $user) {
    return $user->role == "admin";
});
