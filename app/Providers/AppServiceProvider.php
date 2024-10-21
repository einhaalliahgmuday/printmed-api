<?php

namespace App\Providers;

use App\Models\ConsultationRecord;
use App\Policies\ConsultationRecordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        
    }

    public function boot(): void
    {
        Gate::policy(ConsultationRecord::class, ConsultationRecordPolicy::class);
    }
}
