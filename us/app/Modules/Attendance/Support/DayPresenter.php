<?php

namespace App\Modules\Attendance\Support;

use App\Modules\Attendance\Models\AttendanceDay;
use Illuminate\Support\Collection;

/** The one shape an attendance day takes in API responses (app and web). */
final class DayPresenter
{
    /** @return array<string, mixed> */
    public static function day(AttendanceDay $day, string $timezone, bool $withPunches = true): array
    {
        $local = fn ($at) => $at?->copy()->setTimezone($timezone)->toIso8601String();

        $data = [
            'id' => $day->id,
            'user_id' => $day->user_id,
            'date' => $day->date->toDateString(),
            'status' => $day->status,
            'leave_paid' => $day->leave_paid,
            'expected' => $day->expected,
            'scheduled_minutes' => $day->scheduled_minutes,
            'worked_minutes' => $day->worked_minutes,
            'is_late' => $day->is_late,
            'late_minutes' => $day->late_minutes,
            'left_early' => $day->left_early,
            'early_minutes' => $day->early_minutes,
            'missed_checkout' => $day->missed_checkout,
            'first_in_at' => $local($day->first_in_at),
            'last_out_at' => $local($day->last_out_at),
            'is_manual' => $day->is_manual,
            'source' => $day->source,
            'note' => $day->note,
            'needs_review' => false,
        ];

        if ($withPunches) {
            $punches = $day->relationLoaded('punches') ? $day->punches : $day->punches()->with('branch:id,name')->get();
            $active = $punches->whereNull('voided_at')->values();
            $data['punches'] = $active->map(fn ($p) => [
                'id' => $p->id,
                'type' => $p->type,
                'at' => $local($p->punched_at),
                'branch' => $p->branch?->name,
                'distance_m' => $p->distance_m,
                'within_range' => $p->within_range,
                'accuracy_m' => $p->accuracy_m,
                'source' => $p->source,
            ])->all();
            $data['needs_review'] = $active->contains(fn ($p) => $p->within_range === false);
        }

        return $data;
    }

    /**
     * Counts for a run of days — what a month looks like at a glance.
     *
     * @param  Collection<int, AttendanceDay>  $days
     * @return array<string, int>
     */
    public static function summary(Collection $days): array
    {
        $count = fn (string $status) => $days->where('status', $status)->count();

        return [
            'present' => $count('present'),
            'half_day' => $count('half_day'),
            'absent' => $count('absent'),
            'on_leave' => $count('on_leave'),
            'remote' => $count('remote'),
            'day_off' => $count('day_off'),
            'holiday' => $count('holiday'),
            'late' => $days->where('is_late', true)->count(),
            'left_early' => $days->where('left_early', true)->count(),
            'missed_checkout' => $days->where('missed_checkout', true)->count(),
            'worked_minutes' => (int) $days->sum('worked_minutes'),
        ];
    }
}
