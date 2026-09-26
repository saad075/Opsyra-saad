<?php

namespace App\Modules\Attendance\Payroll;

use App\Models\User;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Payroll\Contracts\WorkedHoursProvider;
use Carbon\CarbonInterface;

/**
 * Hours for hourly pay: time clocked on scheduled days, plus client-visit
 * time that was verified (within range) or accepted by a coordinator.
 * Visit time is read from the visits themselves rather than the settled
 * day, so a pay run made just after the period ends still counts its last
 * day. Flagged visits awaiting review, and rejected ones, don't count.
 */
class AttendanceWorkedHours implements WorkedHoursProvider
{
    public function minutesFor(User $staff, CarbonInterface $from, CarbonInterface $to): int
    {
        $range = [$from->toDateString(), $to->toDateString()];

        $clocked = (int) AttendanceDay::where('user_id', $staff->id)
            ->whereBetween('date', $range)
            ->where(fn ($q) => $q->whereNull('expected')->orWhere('expected', '!=', 'visits'))
            ->sum('worked_minutes');

        $visits = (int) Visit::where('user_id', $staff->id)
            ->whereBetween('date', $range)
            ->where('status', 'completed')
            ->get()
            ->sum(fn (Visit $v) => $v->countableMinutes());

        return $clocked + $visits;
    }
}
