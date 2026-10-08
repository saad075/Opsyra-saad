<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Support\Expectation;
use Carbon\CarbonImmutable;

/**
 * A visit-based worker's day comes from their visits: visits never checked
 * into are missed, a visit never checked out is flagged for review, and the
 * day records the visits' time. Countable time excludes visits still
 * awaiting review or rejected, so pay (by the hour of visit time) only
 * follows verified visits.
 */
class VisitDaySettler
{
    /** A visit checked into this long after its planned start is late. */
    public const LATE_AFTER_MINUTES = 15;

    public function settle(User $user, Expectation $expected, bool $isOver, ?CarbonImmutable $now = null): ?AttendanceDay
    {
        $now ??= CarbonImmutable::now();
        $visits = Visit::where('user_id', $user->id)->whereDate('date', $expected->date)->where('status', '!=', 'cancelled')->get();

        if ($isOver) {
            foreach ($visits as $visit) {
                if ($visit->status === 'planned' && $now->gte($visit->planned_end->addHour())) {
                    $visit->update(['status' => 'missed']);
                } elseif ($visit->status === 'in_progress' && $now->gte($visit->planned_end->addHours(4))) {
                    // Time unknown: nothing counts until a coordinator sets it.
                    $visit->update(['status' => 'completed', 'missed_checkout' => true, 'worked_minutes' => 0, 'review_status' => 'needs_review']);
                }
            }
        }

        $day = AttendanceDay::where('user_id', $user->id)->whereDate('date', $expected->date)->first();

        if ($visits->isEmpty() && ! $day) {
            if (! $isOver) {
                return null;
            }

            $day = new AttendanceDay(['user_id' => $user->id, 'date' => $expected->date, 'source' => 'system']);
        }

        $day ??= new AttendanceDay(['user_id' => $user->id, 'date' => $expected->date, 'source' => 'app']);

        $done = $visits->whereIn('status', ['completed', 'in_progress']);
        $firstIn = $done->pluck('checked_in_at')->filter()->min();
        $lateMinutes = $done->map(fn (Visit $v) => $v->checked_in_at ? (int) floor(($v->checked_in_at->getTimestamp() - $v->planned_start->getTimestamp()) / 60) : 0)->max() ?? 0;

        $facts = [
            'expected' => 'visits',
            'scheduled_minutes' => min(65_535, (int) $visits->sum(fn (Visit $v) => $v->plannedMinutes())),
            'worked_minutes' => min(65_535, (int) $visits->sum(fn (Visit $v) => $v->countableMinutes())),
            'first_in_at' => $firstIn,
            'last_out_at' => $done->pluck('checked_out_at')->filter()->max(),
            'is_late' => $lateMinutes > self::LATE_AFTER_MINUTES,
            'late_minutes' => $lateMinutes > self::LATE_AFTER_MINUTES ? $lateMinutes : 0,
            'left_early' => false,
            'early_minutes' => 0,
            'missed_checkout' => $visits->contains('missed_checkout', true),
            'work_schedule_id' => null,
        ];

        if (! $day->is_manual) {
            $facts['status'] = match (true) {
                $done->where('status', 'in_progress')->isNotEmpty() && ! $isOver => 'open',
                $done->isNotEmpty() => 'present',
                $visits->isNotEmpty() && ! $isOver => 'open',
                $visits->isNotEmpty() => 'absent', // every visit missed
                $expected->holidayName !== null => 'holiday',
                default => 'day_off',
            };
        }

        // Settled only once no visit is still open (an overnight stay may run on).
        $settled = $isOver && $visits->whereIn('status', Visit::OPEN)->isEmpty();

        $day->fill($facts + ($settled ? ['closed_at' => $day->closed_at ?? $now] : []))->save();

        return $day;
    }
}
