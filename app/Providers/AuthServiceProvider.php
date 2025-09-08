<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Staff (admin o agent)
        Gate::define('view-staff', fn($user) => $user->hasAnyRole(['admin', 'agent']));

        // Solo admin
        Gate::define('view-admin-only', fn($user) => $user->hasRole('admin'));

        // Applicants
        Gate::define('view-applicant', fn($user) => $user->hasRole('applicant'));
    }
}
