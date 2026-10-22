<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->defineGates();
    }

    protected function defineGates(): void
    {
        // Admin access: owner or admin role
        Gate::define('admin', function ($user) {
            return $user->isAdmin();
        });

        // Finance access: owner, admin, or finance role
        Gate::define('finance', function ($user) {
            return $user->isFinance();
        });

        // Owner only access
        Gate::define('owner', function ($user) {
            return $user->isOwner();
        });

        // Company management - owner only
        Gate::define('manage-company', function ($user) {
            return $user->isOwner();
        });

        // User management - owner or admin
        Gate::define('manage-users', function ($user) {
            return $user->isAdmin();
        });

        // Customer management - owner or admin
        Gate::define('view-customers', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('manage-customers', function ($user) {
            return $user->isAdmin();
        });

        // Jobs management - owner or admin
        Gate::define('view-jobs', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('manage-jobs', function ($user) {
            return $user->isAdmin();
        });

        // Workers/Casuals management - owner or admin
        Gate::define('view-workers', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('manage-workers', function ($user) {
            return $user->isAdmin();
        });

        // Invoices - owner, admin, or finance
        Gate::define('view-invoices', function ($user) {
            return $user->isFinance();
        });

        Gate::define('manage-invoices', function ($user) {
            return $user->isFinance();
        });

        // Payments - owner, admin, or finance
        Gate::define('view-payments', function ($user) {
            return $user->isFinance();
        });

        Gate::define('manage-payments', function ($user) {
            return $user->isFinance();
        });

        // Expenses - owner, admin, or finance
        Gate::define('view-expenses', function ($user) {
            return $user->isFinance();
        });

        Gate::define('manage-expenses', function ($user) {
            return $user->isFinance();
        });

        // Reports - owner, admin, or finance
        Gate::define('view-reports', function ($user) {
            return $user->isFinance();
        });

        // Accounting - view for all finance roles, manage for owner/admin only
        Gate::define('view-accounting', function ($user) {
            return $user->isFinance();
        });

        Gate::define('manage-accounting', function ($user) {
            return $user->isAdmin();
        });

        // Departments - owner or admin
        Gate::define('view-departments', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('manage-departments', function ($user) {
            return $user->isAdmin();
        });
    }
}
