<?php

namespace App\Modules\Hr\Console;

use App\Models\User;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Console\Command;

/**
 * Adapted from SLMS360's staff:activate-pending. Deliberately does not use
 * TenantAwareJob: it spans every tenant, so it switches context per tenant
 * with runAs() (RLS would otherwise hide every row). Also skips anyone whose
 * last working day has already passed — SLMS360 could activate such a person
 * and rely on staff:process-offboarding running afterwards in the same minute.
 */
class ActivatePendingStaffCommand extends Command
{
    protected $signature = 'staff:activate-pending';

    protected $description = 'Activate staff onboarded with a future join date once that date arrives';

    public function handle(TenantContext $tenantContext): int
    {
        $total = 0;

        Tenant::query()->each(function (Tenant $tenant) use ($tenantContext, &$total) {
            $total += $tenantContext->runAs($tenant->id, fn () => User::where('status', 'pending')
                ->whereDate('joined_at', '<=', today())
                ->where(fn ($q) => $q->whereNull('lefted_at')->orWhereDate('lefted_at', '>=', today()))
                ->update(['status' => 'active']));
        });

        $this->info("Activated {$total} staff member(s).");

        return self::SUCCESS;
    }
}
