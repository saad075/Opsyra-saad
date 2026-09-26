<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Attendance\Models\AttendanceDay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Settles finished days (SLMS360's StaffDailyAttendanceCommand): a working
 * day nobody clocked in on becomes absent (remote for remote staff), days
 * off and holidays are recorded as such, and a clock-in never clocked out
 * becomes a missed checkout. Looks back a week, so a missed run catches up;
 * never before the person joined or the company started keeping
 * attendance. Visit-based days are settled from their visits instead.
 */
class DayCloser
{
    private const LOOK_BACK_DAYS = 7;

    public function __construct(private WorkCalendar $calendar, private DayEvaluator $evaluator) {}

    /** @return int days settled */
    public function closeFor(User $user, ?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $today = $now->setTimezone($this->calendar->timezoneFor($user))->startOfDay();
        $from = $today->subDays(self::LOOK_BACK_DAYS);

        if ($start = $this->calendar->trackingStartsOn()) {
            $from = $from->max(CarbonImmutable::parse($start, $today->timezone));
        }

        // One read for the whole window; only unsettled days get locked and judged.
        $closed = AttendanceDay::where('user_id', $user->id)
            ->whereBetween('date', [$from->toDateString(), $today->subDay()->toDateString()])
            ->whereNotNull('closed_at')
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->flip();

        $settled = 0;

        for ($date = $from; $date->lt($today); $date = $date->addDay()) {
            if (! $closed->has($date->toDateString())) {
                $settled += $this->closeDay($user, $date->toDateString(), $now) ? 1 : 0;
            }
        }

        return $settled;
    }

    public function closeDay(User $user, string $date, CarbonImmutable $now): bool
    {
        $expected = $this->calendar->expectationFor($user, $date);

        if (! $expected->isTracked() || $expected->kind === 'visits' || ! $this->calendar->isOver($user, $date, $now, $expected)) {
            return false;
        }

        return DB::transaction(function () use ($user, $date, $expected, $now) {
            $day = AttendanceDay::where('user_id', $user->id)->whereDate('date', $date)->lockForUpdate()->first();

            if ($day?->closed_at) {
                return false;
            }

            $day ??= AttendanceDay::create(['user_id' => $user->id, 'date' => $date, 'status' => 'absent', 'source' => 'system']);

            $this->evaluator->evaluate($day, $expected, $user, isOver: true);
            $day->update(['closed_at' => $now]);

            return true;
        });
    }
}
