<?php

namespace App\Modules\Auth;

use App\Models\User;
use App\Modules\Auth\Models\TenantAccessToken;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Tokens record their tenant, so bearer-token auth can load the user
        // from the RLS-protected users table (see TenantAccessToken).
        Sanctum::usePersonalAccessTokenModel(TenantAccessToken::class);

        Gate::define('view-login-logs', fn (User $user) => $user->can('HRMS-ViewLoginLogs'));
    }
}
