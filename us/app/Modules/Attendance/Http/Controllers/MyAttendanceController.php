<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Models\AttendancePunch;
use App\Modules\Attendance\Models\Holiday;
use App\Modules\Attendance\Models\WorkScheduleDay;
use App\Modules\Attendance\Services\AttendanceClock;
use App\Modules\Attendance\Services\WorkCalendar;
use App\Modules\Attendance\Support\DayPresenter;
use App\Modules\Hr\Models\UserBranch;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * The staff app's attendance API — the same jobs as SLMS360's app
 * endpoints (attendance/today, mark, current-month, previous-month,
 * shift/my-shift), each only ever about the signed-in person.
 */
class MyAttendanceController extends Controller
{
    public function __construct(private WorkCalendar $calendar) {}

    /** Today's plan, what's been clocked, and whether the next tap is in or out. */
    public function today(Request $request)
    {
        $user = $request->user();
        $tz = $this->calendar->timezoneFor($user);

        $last = AttendancePunch::with('day')->where('user_id', $user->id)->whereNull('voided_at')->latest('punched_at')->latest('id')->first();
        $clockedIn = $last && $last->type === 'in' && $last->punched_at->diffInHours(now()) < 20;
        $date = $clockedIn ? $last->day->date->toDateString() : $this->calendar->today($user)->toDateString();

        $day = AttendanceDay::where('user_id', $user->id)->whereDate('date', $date)->first();
        $branches = UserBranch::with('branch')->where('user_id', $user->id)->get()->pluck('branch')->filter()->where('status', true);

        return response()->json([
            'status' => true,
            'message' => 'Today',
            'data' => [
                'date' => $date,
                'timezone' => $tz,
                'attendance_mode' => $user->attendance_mode,
                'expected' => $this->calendar->expectationFor($user, $date)->toArray(),
                'day' => $day ? DayPresenter::day($day, $tz) : null,
                'next_action' => $clockedIn ? 'out' : 'in',
                'branches' => $branches->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'latitude' => $b->latitude,
                    'longitude' => $b->longitude,
                    'geofence_radius_m' => $b->geofence_radius_m,
                ])->values(),
            ],
        ]);
    }

    public function clock(Request $request, AttendanceClock $clock)
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => ['nullable', 'in:in,out'],
            // Only one of the person's own branches.
            'branch_id' => ['nullable', 'integer', ValidationRule::exists('user_branches', 'branch_id')->where('user_id', $user->id)->where('tenant_id', $user->tenant_id)],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'device' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'in:app,web'], // the staff app, or Opsyra in a browser
        ]);

        $punch = $clock->clock($user, $validated + ['source' => 'app']);
        $tz = $this->calendar->timezoneFor($user);

        $message = $punch->type === 'in' ? 'Clocked in.' : 'Clocked out.';

        if ($punch->within_range === false) {
            $message .= " You’re {$punch->distance_m} m from the branch, so HR will review this.";
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => DayPresenter::day($punch->day->fresh(), $tz) + ['punch_type' => $punch->type],
        ], 201);
    }

    /** One month of days (defaults to this month), newest first, with totals. */
    public function month(Request $request)
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $user = $request->user();
        $tz = $this->calendar->timezoneFor($user);

        $start = CarbonImmutable::parse(($validated['month'] ?? $this->calendar->today($user)->format('Y-m')).'-01', $tz);
        $days = AttendanceDay::with('punches.branch:id,name')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $start->endOfMonth()->toDateString()])
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'My attendance',
            'data' => [
                'month' => $start->format('Y-m'),
                'summary' => DayPresenter::summary($days),
                'days' => $days->map(fn ($d) => DayPresenter::day($d, $tz))->values(),
            ],
        ]);
    }

    /** My working week and the holidays coming up (SLMS360's shift/my-shift). */
    public function schedule(Request $request)
    {
        $user = $request->user();
        $today = $this->calendar->today($user);
        $schedule = $this->calendar->scheduleFor($user, $today->toDateString());
        $branchIds = UserBranch::where('user_id', $user->id)->pluck('branch_id');

        return response()->json([
            'status' => true,
            'message' => 'My schedule',
            'data' => [
                'attendance_mode' => $user->attendance_mode,
                'schedule' => $schedule ? [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'grace_minutes' => $schedule->grace_minutes,
                    'days' => $schedule->days->map(fn (WorkScheduleDay $d) => [
                        'weekday' => $d->weekday,
                        'name' => WorkScheduleDay::NAMES[$d->weekday],
                        'is_working' => $d->is_working,
                        'start_time' => $d->start_time ? substr($d->start_time, 0, 5) : null,
                        'end_time' => $d->end_time ? substr($d->end_time, 0, 5) : null,
                        'break_minutes' => $d->break_minutes,
                        'required_minutes' => $d->requiredMinutes(),
                    ])->values(),
                ] : null,
                'upcoming_holidays' => Holiday::whereDate('date', '>=', $today->toDateString())
                    ->where(fn ($q) => $q->whereNull('branch_id')->orWhereIn('branch_id', $branchIds))
                    ->orderBy('date')
                    ->limit(10)
                    ->get(['id', 'date', 'name'])
                    ->map(fn ($h) => ['date' => $h->date->toDateString(), 'name' => $h->name]),
            ],
        ]);
    }
}
