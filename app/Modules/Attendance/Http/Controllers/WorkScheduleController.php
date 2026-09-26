<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Exceptions\AttendanceException;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Models\WorkSchedule;
use App\Modules\Attendance\Models\WorkScheduleAssignment;
use App\Modules\Attendance\Models\WorkScheduleDay;
use App\Modules\Attendance\Services\WorkCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Adapted from SLMS360's StaffShiftController (no permission checks, and
 * pay rules stored per shift). Schedules here hold only working times;
 * who works which one is a dated assignment.
 */
class WorkScheduleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-attendance');

        $data = WorkSchedule::with('days')
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->each(function (WorkSchedule $s) {
                $s->staff_count = WorkScheduleAssignment::where('work_schedule_id', $s->id)
                    ->whereDate('starts_on', '<=', today())
                    ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))
                    ->distinct('user_id')
                    ->count('user_id');
            });

        return response()->json(['status' => true, 'message' => 'Work schedules', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-attendance');

        $validated = $this->validateSchedule($request);
        $schedule = DB::transaction(fn () => $this->save(new WorkSchedule, $validated));

        return response()->json(['status' => true, 'message' => 'Work schedule created.', 'data' => $schedule], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-attendance');

        $request->validate(['id' => ['required', 'integer', ValidationRule::exists('work_schedules', 'id')->where('tenant_id', $request->user()->tenant_id)]]);
        $schedule = WorkSchedule::findOrFail($request->id);
        $validated = $this->validateSchedule($request, $schedule);

        if ($schedule->is_default && array_key_exists('is_active', $validated) && ! $validated['is_active']) {
            throw new AttendanceException('Make another schedule the default before switching this one off.');
        }

        $schedule = DB::transaction(fn () => $this->save($schedule, $validated));

        return response()->json(['status' => true, 'message' => 'Work schedule saved. Days already settled keep their times.', 'data' => $schedule]);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-attendance');

        $request->validate(['id' => ['required', 'integer', ValidationRule::exists('work_schedules', 'id')->where('tenant_id', $request->user()->tenant_id)]]);
        $schedule = WorkSchedule::findOrFail($request->id);

        if ($schedule->is_default || WorkScheduleAssignment::where('work_schedule_id', $schedule->id)->exists() || AttendanceDay::where('work_schedule_id', $schedule->id)->exists()) {
            throw new AttendanceException('This schedule is the default or has been used. Switch it off instead.');
        }

        $schedule->delete();

        return response()->json(['status' => true, 'message' => 'Work schedule deleted.']);
    }

    /** Put people on a schedule from a date; their previous assignment ends the day before. */
    public function assign(Request $request)
    {
        $this->authorize('manage-attendance');

        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'work_schedule_id' => ['required', 'integer', ValidationRule::exists('work_schedules', 'id')->where('tenant_id', $tenantId)->where('is_active', true)],
            'user_ids' => ['required', 'array', 'min:1', 'max:500'],
            'user_ids.*' => ['integer', 'distinct', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'starts_on' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            foreach ($validated['user_ids'] as $userId) {
                // Anything starting on/after the new date is replaced; the one running then ends the day before.
                WorkScheduleAssignment::where('user_id', $userId)->whereDate('starts_on', '>=', $validated['starts_on'])->delete();
                WorkScheduleAssignment::where('user_id', $userId)
                    ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $validated['starts_on']))
                    ->update(['ends_on' => now()->parse($validated['starts_on'])->subDay()->toDateString()]);

                WorkScheduleAssignment::create([
                    'user_id' => $userId,
                    'work_schedule_id' => $validated['work_schedule_id'],
                    'starts_on' => $validated['starts_on'],
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return response()->json(['status' => true, 'message' => count($validated['user_ids']).' person(s) moved to this schedule.']);
    }

    /** Who is on which schedule today (people with no assignment follow the default). */
    public function assignments(Request $request)
    {
        $this->authorize('manage-attendance');

        $data = WorkScheduleAssignment::with(['schedule:id,name', 'staff:id,name,designation'])
            ->whereDate('starts_on', '<=', today())
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))
            ->orderBy('user_id')
            ->get();

        return response()->json(['status' => true, 'message' => 'Schedule assignments', 'data' => $data]);
    }

    /**
     * Everyone current, with the schedule they're on today (their own or
     * the default) and how their attendance is tracked.
     */
    public function people(Request $request, WorkCalendar $calendar)
    {
        $this->authorize('manage-attendance');

        $data = User::whereIn('status', ['active', 'pending'])
            ->orderBy('name')
            ->get(['id', 'name', 'designation', 'attendance_mode', 'is_remote', 'status'])
            ->map(function (User $u) use ($calendar) {
                $today = $calendar->today($u)->toDateString();
                $assignment = WorkScheduleAssignment::forUserOn($u->id, $today);
                $schedule = $assignment?->schedule ?? $calendar->scheduleFor($u, $today);

                return $u->only(['id', 'name', 'designation', 'attendance_mode', 'is_remote', 'status']) + [
                    'schedule' => $schedule?->only(['id', 'name']),
                    'on_default' => ! $assignment,
                    'since' => $assignment?->starts_on?->toDateString(),
                ];
            });

        return response()->json(['status' => true, 'message' => 'People and schedules', 'data' => $data]);
    }

    private function validateSchedule(Request $request, ?WorkSchedule $existing = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('work_schedules')->where('tenant_id', $request->user()->tenant_id)->ignore($existing?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'grace_minutes' => ['nullable', 'integer', 'between:0,240'],
            'early_leave_grace_minutes' => ['nullable', 'integer', 'between:0,240'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'days' => ['required', 'array', 'size:7'],
            'days.*.weekday' => ['required', 'integer', 'between:1,7', 'distinct'],
            'days.*.is_working' => ['required', 'boolean'],
            'days.*.start_time' => ['nullable', 'required_if_accepted:days.*.is_working', 'date_format:H:i'],
            'days.*.end_time' => ['nullable', 'required_if_accepted:days.*.is_working', 'date_format:H:i', 'different:days.*.start_time'],
            'days.*.break_minutes' => ['nullable', 'integer', 'between:0,600'],
        ]);
    }

    private function save(WorkSchedule $schedule, array $validated): WorkSchedule
    {
        $isDefault = $validated['is_default'] ?? $schedule->is_default ?? false;

        if ($isDefault) {
            WorkSchedule::where('is_default', true)->when($schedule->exists, fn ($q) => $q->whereKeyNot($schedule->id))->update(['is_default' => false]);
        }

        $schedule->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'grace_minutes' => $validated['grace_minutes'] ?? $schedule->grace_minutes ?? 10,
            'early_leave_grace_minutes' => $validated['early_leave_grace_minutes'] ?? $schedule->early_leave_grace_minutes ?? 10,
            'is_default' => $isDefault,
            'is_active' => $validated['is_active'] ?? $schedule->is_active ?? true,
        ])->save();

        foreach ($validated['days'] as $day) {
            WorkScheduleDay::updateOrCreate(
                ['work_schedule_id' => $schedule->id, 'weekday' => $day['weekday']],
                [
                    'is_working' => $day['is_working'],
                    'start_time' => $day['is_working'] ? $day['start_time'] : null,
                    'end_time' => $day['is_working'] ? $day['end_time'] : null,
                    'break_minutes' => $day['is_working'] ? ($day['break_minutes'] ?? 0) : 0,
                ],
            );
        }

        return $schedule->fresh('days');
    }
}
