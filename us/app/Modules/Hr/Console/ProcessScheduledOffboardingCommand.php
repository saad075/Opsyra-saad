<?php

namespace App\Modules\Hr\Console;

use App\Modules\Hr\Services\StaffOffboarding;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Console\Command;

/**
 * Adapted from SLMS360's staff:process-offboarding. Cross-tenant, so it
 * switches context per tenant with runAs() rather than using TenantAwareJob.
 * The actual rules live in StaffOffboarding::processDue().
 */
class ProcessScheduledOffboardingCommand extends Command
{
    protected $signature = 'staff:process-offboarding';

    protected $description = 'Move staff whose last working day has passed to offboarded';

    public function handle(TenantContext $tenantContext, StaffOffboarding $offboarding): int
    {
        $total = 0;

        Tenant::query()->each(function (Tenant $tenant) use ($tenantContext, $offboarding, &$total) {
            $total += $tenantContext->runAs($tenant->id, fn () => $offboarding->processDue());
        });

        $this->info("Offboarded {$total} staff member(s).");

        return self::SUCCESS;
    }
}
