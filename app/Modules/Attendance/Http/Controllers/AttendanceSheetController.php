<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Services\AttendanceEditor;
use App\Modules\Attendance\Services\WorkCalendar;
use App\Modules\Attendance\Support\DayPresenter;
use App\Modules\Hr\Models\UserBranch;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * HR's view: everyone's attendance for a day, one person's month, and
 * marking or correcting a day (SLMS360's daily/single attendance,
 * mark-manual and update-status — which had no permission checks).
 */
class AttendanceSheetController extends Controller
{
    public function __construct(private WorkCalendar $calendar) {}

    public function daily(Request $request)
    {
        $this->authorize('manage-attendance');

        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer', ValidationRule::exists('branches', 'id')->where('tenant_id', $tenantId)],
        ]);

        $date = $validated['date'] ?? CarbonImmutable::now($this->calendar->timezoneFor($request->user()))->toDateString();

        $staff = User::whereIn('status', ['active', 'pending'])
            ->where('attendance_mode', '!=', 'not_tracked')
            ->when($validated['branch_id'] ?? null, fn ($q, $branch) => $q->whereIn('id', UserBranch::where('branch_id', $branch)->select('user_id')))
            ->orderBy('name')
            ->get();

        $days = AttendanceDay::with('punches.branch:id,name')
            ->whereDate('date', $date)
            ->whereIn('user_id', $staff->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $rows = $staff->map(function (User $person) use ($date, $days) {
            $expected = $this->calendar->expectationFor($person, $date);

            if (! $expected->isTracked()) {
                return null;
            }

            $tz = $expected->timezone;

            return [
                'staff' => ['id' => $person->id, 'name' => $person->name, 'designation' => $person->designation, 'attendance_mode' => $person->attendance_mode],
                'expected' => $expected->toArray(),
                'day' => isset($days[$person->id]) ? DayPresenter::day($days[$person->id], $tz) : null,
            ];
        })->filter()->values();

        $present = fn ($r) => in_array($r['day']['status'] ?? null, ['present', 'open', 'half_day', 'remote'], true);

        return response()->json([
            'status' => true,
            'message' => 'Attendance',
            'data' => [
                'date' => $date,
                'summary' => [
                    'expected' => $rows->whereIn('expected.kind', ['work', 'visits'])->count(),
                    'in' => $rows->filter($present)->count(),
                    'late' => $rows->where('day.is_late', true)->count(),
                    'absent' => $rows->where('day.status', 'absent')->count(),
                    'on_leave' => $rows->where('day.status', 'on_leave')->count(),
                    'not_in_yet' => $rows->filter(fn ($r) => $r['expected']['kind'] === 'work' && ! $r['day'])->count(),
                    'needs_review' => $rows->where('day.needs_review', true)->count(),
                ],
                'rows' => $rows,
            ],
        ]);
    }

    public function personMonth(Request $request)
    {
        $this->authorize('manage-attendance');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $person = User::findOrFail($validated['user_id']);
        $tz = $this->calendar->timezoneFor($person);
        $start = CarbonImmutable::parse(($validated['month'] ?? $this->calendar->today($person)->format('Y-m')).'-01', $tz);

        $days = AttendanceDay::with('punches.branch:id,name')
            ->where('user_id', $person->id)
            ->whereBetween('date', [$start->toDateString(), $start->endOfMonth()->toDateString()])
            ->orderBy('date')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Attendance month',
            'data' => [
                'staff' => $person->only(['id', 'name', 'designation', 'attendance_mode']),
                'month' => $start->format('Y-m'),
                'timezone' => $tz,
                'summary' => DayPresenter::summary($days),
                'days' => $days->map(fn ($d) => DayPresenter::day($d, $tz))->values(),
            ],
        ]);
    }

    /**
     * Mark or correct a day: set clock times, set a status (e.g. on leave,
     * paid or unpaid), or hand the day back to the clock.
     */
    public function mark(Request $request, AttendanceEditor $editor)
    {
        $this->authorize('manage-attendance');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'date' => ['required', 'date'],
            'action' => ['required', 'in:times,status,reset'],
            'in' => ['nullable', 'required_if:action,times', 'date_format:H:i'],
            'out' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', 'required_if:action,status', ValidationRule::in(AttendanceDay::MANUAL_STATUSES)],
            'leave_paid' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $person = User::findOrFail($validated['user_id']);
        $actor = $request->user();
        $date = CarbonImmutable::parse($validated['date'])->toDateString();

        $day = match ($validated['action']) {
            'times' => $editor->setTimes($person, $date, $validated['in'], $validated['out'] ?? null, $actor),
            'status' => $editor->setStatus($person, $date, $validated['status'], $validated['leave_paid'] ?? null, $validated['note'] ?? null, $actor),
            'reset' => $editor->reset($person, $date, $actor),
        };

        return response()->json(['status' => true, 'message' => 'Attendance updated.', 'data' => DayPresenter::day($day, $this->calendar->timezoneFor($person))]);
    }
}
