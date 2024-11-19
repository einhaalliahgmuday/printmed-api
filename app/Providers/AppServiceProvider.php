<?php

namespace App\Providers;

use App\Models\Consultation;
use App\Policies\ConsultationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        
    }

    public function boot(): void
    {
        Gate::define('is-assigned-physician', [ConsultationPolicy::class, 'isAssignedPhysician']);
    }
}
