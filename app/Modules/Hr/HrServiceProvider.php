<?php

namespace App\Modules\Hr;

use App\Models\User;
use App\Modules\Hr\Console\ActivatePendingStaffCommand;
use App\Modules\Hr\Console\ProcessScheduledOffboardingCommand;
use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Platform\Models\Tenant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Named Gate abilities rather than a Policy bound to User::class — User
 * already carries broader auth concerns (it's the Authenticatable model),
 * and staff-management actions are only one of the things that might one day
 * need authorizing against a User target. Every mutating HR action checks
 * permission AND (where there's a specific target) tenant ownership — the
 * issue class the v2.5.0 SLMS360 review found; see RbacServiceProvider /
 * RolePolicy for the same pattern applied via a real Policy.
 */
class HrServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('manage-hr', function (User $user) {
            return $user->can('HRMS-StaffManagement');
        });

        Gate::define('manage-staff-member', function (User $user, User $target) {
            return $user->can('HRMS-StaffManagement') && $target->tenant_id === $user->tenant_id;
        });

        Gate::define('update-staff-permissions', function (User $user) {
            return $user->can('HRMS-StaffPermissionUpdate');
        });

        // SLMS360's one shared L2 authority (its 2026_09_11_000003 migration
        // standardised every approval-gated feature onto this single
        // permission rather than one pair per module). Onboarding and
        // offboarding use it now; Salaries will too.
        Gate::define('approve-hr-requests', function (User $user) {
            return $user->can('HRMS-Approval-L2');
        });

        // Every new tenant starts with its country's default departure reasons.
        Tenant::created(fn (Tenant $tenant) => DepartureReason::seedDefaultsFor($tenant));

        if ($this->app->runningInConsole()) {
            $this->commands([
                ActivatePendingStaffCommand::class,
                ProcessScheduledOffboardingCommand::class,
            ]);
        }
    }
}
