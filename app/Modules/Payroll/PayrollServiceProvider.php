<?php

namespace App\Modules\Payroll;

use App\Models\User;
use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Platform\Models\Tenant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Salaries hub, adapted from SLMS360's Client/Staff/SalaryController and
 * SalaryClaimController — which between them checked exactly one
 * permission (which approval label to stamp). Anyone logged in could
 * change anyone's salary or bank details there. Every action here goes
 * through one of these abilities; target ownership is enforced by
 * BelongsToTenant + RLS plus tenant-scoped validation on every id.
 * Permission names are SLMS360's own (docs/research/salaries.md).
 */
class PayrollServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('manage-salaries', fn (User $user) => $user->can('HRMS-SalaryManagement'));

        Gate::define('manage-salary-adjustments', fn (User $user) => $user->can('HRMS-StaffAdjustments'));

        // First-level approval: an L1 holder, or an L2 holder (who may also
        // approve outright — see Payslips::approve()).
        Gate::define('approve-salaries-l1', fn (User $user) => $user->can('HRMS-Approval-L1') || $user->can('HRMS-Approval-L2'));

        Gate::define('approve-salaries-l2', fn (User $user) => $user->can('HRMS-Approval-L2'));

        // Seeing everyone's pay runs and payslips: whoever runs payroll, and
        // whoever approves it. Everyone else sees only their own payslips.
        Gate::define('view-payroll', fn (User $user) => $user->can('manage-salaries') || $user->can('approve-salaries-l1'));

        Tenant::created(fn (Tenant $tenant) => PayComponent::seedDefaultsFor($tenant));
    }
}
