<?php

namespace App\Modules\Rbac;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Rbac\Console\SyncPermissionsCommand;
use App\Modules\Rbac\Models\Role;
use App\Modules\Rbac\Policies\RolePolicy;
use App\Modules\Rbac\Support\DefaultRoles;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel's policy auto-discovery assumes models live under App\Models; ours
 * don't (App\Modules\Rbac\Models\Role), so policies are registered
 * explicitly here rather than relying on convention.
 */
class RbacServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);

        // Every new tenant starts with the default roles (Administrator, HR
        // Manager, Payroll Officer, Approver, Senior approver).
        Tenant::created(fn (Tenant $tenant) => DefaultRoles::seedFor($tenant));

        if ($this->app->runningInConsole()) {
            $this->commands([SyncPermissionsCommand::class]);
        }
    }
}
