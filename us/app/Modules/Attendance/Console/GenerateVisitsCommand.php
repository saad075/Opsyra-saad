<?php

namespace App\Modules\Attendance\Console;

use App\Modules\Attendance\Services\VisitScheduler;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Console\Command;

/** Daily: keeps every visit plan's visits generated two weeks ahead. */
class GenerateVisitsCommand extends Command
{
    protected $signature = 'visits:generate';

    protected $description = 'Create the coming two weeks of visits from every visit plan';

    public function handle(TenantContext $tenantContext, VisitScheduler $scheduler): int
    {
        $total = 0;

        Tenant::query()->each(function (Tenant $tenant) use ($tenantContext, $scheduler, &$total) {
            $total += $tenantContext->runAs($tenant->id, fn () => $scheduler->generate());
        });

        $this->info("Created {$total} visit(s).");

        return self::SUCCESS;
    }
}
