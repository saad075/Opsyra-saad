<?php

namespace App\Modules\Attendance\Console;

use App\Models\User;
use App\Modules\Attendance\Services\DayCloser;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Console\Command;

/**
 * Hourly: settles every finished day for everyone whose attendance is
 * tracked. Hourly rather than nightly because "finished" depends on each
 * branch's timezone and on night shifts; a day is only ever settled once.
 */
class CloseOutAttendanceCommand extends Command
{
    protected $signature = 'attendance:close-out';

    protected $description = 'Settle finished attendance days: absences, days off, holidays and missed check-outs';

    public function handle(TenantContext $tenantContext, DayCloser $closer): int
    {
        $total = 0;

        Tenant::query()->each(function (Tenant $tenant) use ($tenantContext, $closer, &$total) {
            $tenantContext->runAs($tenant->id, function () use ($closer, &$total) {
                User::whereIn('status', ['active', 'pending'])
                    ->where('attendance_mode', 'schedule')
                    ->each(function (User $user) use ($closer, &$total) {
                        $total += $closer->closeFor($user);
                    });
            });
        });

        $this->info("Settled {$total} attendance day(s).");

        return self::SUCCESS;
    }
}
