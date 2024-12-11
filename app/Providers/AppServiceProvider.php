<?php

namespace App\Providers;

use App\Policies\AdminPolicy;
use App\Policies\PhysicianAccessPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        
    }

    public function boot(): void
    {
        Gate::define('is-assigned-physician', [PhysicianAccessPolicy::class, 'isAssignedPhysician']);
        Gate::define('is-authorized-admin-action', [AdminPolicy::class, 'isAuthorizedAdminAction']);
    }
}
