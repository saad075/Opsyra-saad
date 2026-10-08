<?php

namespace App\Modules\Payroll\Support;

use App\Models\User;
use App\Modules\Payroll\Contracts\WorkedHoursProvider;
use Carbon\CarbonInterface;

/** The default when no module records time: no hours, so hourly pay is nil. */
final class NoWorkedHours implements WorkedHoursProvider
{
    public function minutesFor(User $staff, CarbonInterface $from, CarbonInterface $to): int
    {
        return 0;
    }
}
