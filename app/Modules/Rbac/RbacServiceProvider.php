<?php

namespace App\Modules\Rbac;

use App\Modules\Rbac\Models\Role;
use App\Modules\Rbac\Policies\RolePolicy;
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
    }
}
