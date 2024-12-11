<?php

namespace App\Policies;

use App\Models\User;

class AdminPolicy
{
    public function isAuthorizedAdminAction(User $user, $role)
    {
        if ($user->role != "super admin" && ($role == "admin" || $role == "super admin")) {
            return false;
        }

        return true;
    }
}
