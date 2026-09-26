<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Models\AttendanceCorrection;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Models\AttendancePunch;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Services\WorkCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * The Attendance module's own dashboard (the Horilla pattern: one per
 * module): the last two weeks at a glance, who is often late or absent this
 * month, and what's waiting for someone. Today's detail is the daily sheet.
 */
class AttendanceDashboardController extends Controller
{
    public function __invoke(Request $request, WorkCalendar $calendar)
    {
        $this->authorize('manage-attendance');

        $today = CarbonImmutable::now($calendar->timezoneFor($request->user()))->startOfDay();
        $from = $today->subDays(13);
        $monthStart = $today->startOfMonth();

        $days = AttendanceDay::whereBetween('date', [$from->toDateString(), $today->toDateString()])
            ->get(['user_id', 'date', 'status', 'is_late', 'left_early', 'missed_checkout']);

        $trend = collect(range(0, 13))->map(function (int $i) use ($from, $days) {
            $date = $from->addDays($i)->toDateString();
            $onDay = $days->filter(fn ($d) => $d->date->toDateString() === $date);

            return [
                'date' => $date,
                'present' => $onDay->whereIn('status', ['present', 'open', 'remote', 'half_day'])->count(),
                'absent' => $onDay->where('status', 'absent')->count(),
                'on_leave' => $onDay->where('status', 'on_leave')->count(),
                'late' => $onDay->where('is_late', true)->count(),
            ];
        })->values();

        $month = AttendanceDay::whereBetween('date', [$monthStart->toDateString(), $today->toDateString()])->get(['user_id', 'status', 'is_late']);
        $names = User::whereIn('id', $month->pluck('user_id')->unique())->pluck('name', 'id');
        $top = fn ($rows) => $rows->groupBy('user_id')
            ->map(fn ($g, $userId) => ['user_id' => $userId, 'name' => $names[$userId] ?? '—', 'count' => $g->count()])
            ->sortByDesc('count')->values()->take(5);

        $worked = $month->whereIn('status', ['present', 'remote', 'half_day', 'absent'])->count();

        return response()->json([
            'status' => true,
            'message' => 'Attendance dashboard',
            'data' => [
                'trend' => $trend,
                'month' => [
                    'attendance_rate' => $worked ? round(($worked - $month->where('status', 'absent')->count()) / $worked * 100) : null,
                    'lates' => $month->where('is_late', true)->count(),
                    'absences' => $month->where('status', 'absent')->count(),
                    'most_late' => $top($month->where('is_late', true)),
                    'most_absent' => $top($month->where('status', 'absent')),
                ],
                'waiting' => [
                    'corrections' => AttendanceCorrection::where('status', 'open')->count(),
                    'far_clock_ins' => AttendancePunch::whereNull('voided_at')->where('within_range', false)->where('punched_at', '>=', $today->subDays(7))->count(),
                    'missed_checkouts' => $days->where('missed_checkout', true)->filter(fn ($d) => $d->date->gte($today->subDays(7)))->count(),
                    'visits_to_review' => Visit::where('review_status', 'needs_review')->count(),
                ],
            ],
        ]);
    }
}
