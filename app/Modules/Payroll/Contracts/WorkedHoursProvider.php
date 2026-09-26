<?php

namespace App\Modules\Payroll\Contracts;

use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Where hourly pay gets its hours. Payroll doesn't know how time is
 * recorded; the Attendance module answers (verified clocked time, or
 * accepted client-visit time). Without a provider, nobody has any hours.
 */
interface WorkedHoursProvider
{
    /** Minutes that count for pay between two dates (inclusive). */
    public function minutesFor(User $staff, CarbonInterface $from, CarbonInterface $to): int;
}
