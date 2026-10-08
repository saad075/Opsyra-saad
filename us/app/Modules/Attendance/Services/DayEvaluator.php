<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Support\AttendancePolicy;
use App\Modules\Attendance\Support\Expectation;
use Carbon\CarbonImmutable;

/**
 * Works out a day's facts from its clock punches and what the calendar
 * expected — the only place this happens (SLMS360 had it in the check-in
 * endpoint, the nightly command and manual marking, which drifted apart).
 *
 * $isOver: the day can no longer change by itself (the nightly close-out):
 * no punches then means absent (or remote / day off / holiday), and a
 * clock-in with no clock-out becomes a missed checkout.
 */
class DayEvaluator
{
    public function evaluate(AttendanceDay $day, Expectation $expected, User $user, bool $isOver): AttendanceDay
    {
        $punches = $day->punches()->whereNull('voided_at')->orderBy('punched_at')->get();

        [$worked, $open] = $this->pairUp($punches);

        $facts = [
            'expected' => $expected->kind,
            'scheduled_minutes' => $expected->requiredMinutes,
            'work_schedule_id' => $expected->schedule?->id,
            'worked_minutes' => $worked,
            'first_in_at' => $punches->firstWhere('type', 'in')?->punched_at,
            'last_out_at' => $punches->where('type', 'out')->last()?->punched_at,
            'missed_checkout' => $open && $isOver,
            'is_late' => false,
            'late_minutes' => 0,
            'left_early' => false,
            'early_minutes' => 0,
        ];

        if ($expected->isWorkDay() && $facts['first_in_at']) {
            $late = $this->minutesAfter($expected->start, $facts['first_in_at']);
            $facts['is_late'] = $late > ($expected->schedule?->grace_minutes ?? 0);
            $facts['late_minutes'] = $facts['is_late'] ? $late : 0;
        }

        if ($expected->isWorkDay() && $facts['last_out_at'] && ! $open) {
            $early = $this->minutesAfter($facts['last_out_at'], $expected->end);
            $facts['left_early'] = $early > ($expected->schedule?->early_leave_grace_minutes ?? 0);
            $facts['early_minutes'] = $facts['left_early'] ? $early : 0;
        }

        // HR set this day by hand: keep their status, refresh the facts only.
        if (! $day->is_manual) {
            $facts['status'] = $this->status($punches->isNotEmpty(), $open, $isOver, $worked, $expected, $user);
        }

        $day->fill($facts)->save();

        return $day;
    }

    private function status(bool $hasPunches, bool $open, bool $isOver, int $worked, Expectation $expected, User $user): string
    {
        if (! $hasPunches) {
            return match ($expected->kind) {
                'day_off' => 'day_off',
                'holiday' => 'holiday',
                'work' => $user->is_remote ? 'remote' : 'absent',
                default => 'absent',
            };
        }

        if ($open && ! $isOver) {
            return 'open';
        }

        // Missed checkout: they came in; what it costs is the policy's call.
        if ($open) {
            return 'present';
        }

        if ($expected->isWorkDay() && $expected->requiredMinutes > 0) {
            $share = $worked / $expected->requiredMinutes * 100;
            $policy = AttendancePolicy::all();

            if ($share < $policy['absent_below_percent']) {
                return 'absent';
            }

            if ($share < $policy['half_day_below_percent']) {
                return 'half_day';
            }
        }

        return 'present';
    }

    /**
     * Minutes worked across in→out pairs; a trailing "in" is still open.
     *
     * @return array{0: int, 1: bool} [worked minutes, still clocked in]
     */
    private function pairUp($punches): array
    {
        $worked = 0;
        $in = null;

        foreach ($punches as $punch) {
            if ($punch->type === 'in') {
                $in ??= $punch->punched_at;
            } elseif ($in) {
                $worked += (int) floor(CarbonImmutable::parse($in)->diffInSeconds($punch->punched_at) / 60);
                $in = null;
            }
        }

        return [min($worked, 65_535), $in !== null];
    }

    /** Whole minutes from $from to $to (negative if $to is earlier). */
    private function minutesAfter($from, $to): int
    {
        return (int) floor((CarbonImmutable::parse($to)->getTimestamp() - CarbonImmutable::parse($from)->getTimestamp()) / 60);
    }
}
