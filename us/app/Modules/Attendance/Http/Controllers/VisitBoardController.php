<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Services\VisitEditor;
use App\Modules\Attendance\Services\VisitScheduler;
use App\Modules\Attendance\Services\WorkCalendar;
use App\Modules\Attendance\Support\VisitPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * The coordinator's day: every caregiver's visits, one-off visits, moving,
 * reassigning and cancelling, and the queue of flagged check-ins to review.
 */
class VisitBoardController extends Controller
{
    public function __construct(private WorkCalendar $calendar) {}

    public function day(Request $request)
    {
        $this->authorize('plan-visits');

        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer', ValidationRule::exists('branches', 'id')->where('tenant_id', $tenantId)],
        ]);

        $date = $validated['date'] ?? CarbonImmutable::now($this->calendar->timezoneFor($request->user()))->toDateString();

        $visits = Visit::with(['location', 'staff:id,name,designation'])
            ->whereDate('date', $date)
            ->when($validated['branch_id'] ?? null, fn ($q, $b) => $q->whereHas('location', fn ($l) => $l->where('branch_id', $b)))
            ->orderBy('planned_start')
            ->get();

        // Everyone who works by visits, plus anyone else with a visit that day.
        $staff = User::where(fn ($q) => $q->where('attendance_mode', 'visits')->whereIn('status', ['active', 'pending']))
            ->orWhereIn('id', $visits->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'designation']);

        $tz = fn (User $u) => $this->calendar->timezoneFor($u);

        return response()->json([
            'status' => true,
            'message' => 'Visits',
            'data' => [
                'date' => $date,
                'summary' => [
                    'planned' => $visits->where('status', '!=', 'cancelled')->count(),
                    'completed' => $visits->where('status', 'completed')->count(),
                    'in_progress' => $visits->where('status', 'in_progress')->count(),
                    'missed' => $visits->where('status', 'missed')->count(),
                    'needs_review' => $visits->where('review_status', 'needs_review')->count(),
                ],
                'caregivers' => $staff->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'designation' => $u->designation,
                    'visits' => $visits->where('user_id', $u->id)->map(fn (Visit $v) => VisitPresenter::visit($v, $tz($u)))->values(),
                ])->values(),
            ],
        ]);
    }

    /** Who can be given visits: visit-based staff first, then everyone else current. */
    public function caregivers()
    {
        $this->authorize('plan-visits');

        $data = User::whereIn('status', ['active', 'pending'])
            ->orderByRaw("attendance_mode = 'visits' desc")
            ->orderBy('name')
            ->get(['id', 'name', 'designation', 'attendance_mode']);

        return response()->json(['status' => true, 'message' => 'Caregivers', 'data' => $data]);
    }

    /** A one-off visit (not from a plan). */
    public function store(Request $request, VisitScheduler $scheduler)
    {
        $this->authorize('plan-visits');

        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'visit_location_id' => ['required', 'integer', ValidationRule::exists('visit_locations', 'id')->where('tenant_id', $tenantId)->where('is_active', true)],
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)->whereIn('status', ['active', 'pending'])],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
        ]);

        $staff = User::findOrFail($validated['user_id']);
        $day = CarbonImmutable::parse($validated['date'], $this->calendar->timezoneFor($staff))->startOfDay();
        [$start, $end] = $scheduler->times($day, $validated['start_time'], $validated['end_time']);

        $visit = Visit::create([
            'visit_location_id' => $validated['visit_location_id'],
            'user_id' => $staff->id,
            'date' => $day->toDateString(),
            'planned_start' => $start,
            'planned_end' => $end,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['status' => true, 'message' => 'Visit added.', 'data' => VisitPresenter::visit($visit->load(['location', 'staff']), $this->calendar->timezoneFor($staff))], 201);
    }

    public function update(Request $request, VisitEditor $editor)
    {
        $this->authorize('plan-visits');

        $tenantId = $request->user()->tenant_id;
        $visit = $this->find($request);
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)->whereIn('status', ['active', 'pending'])],
            'date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $visit = $editor->reschedule($visit, $validated['user_id'] ?? null, $validated['date'] ?? null, $validated['start_time'] ?? null, $validated['end_time'] ?? null, $request->user());

        return $this->respond($visit, 'Visit updated.');
    }

    public function cancel(Request $request, VisitEditor $editor)
    {
        $this->authorize('plan-visits');

        $visit = $editor->cancel($this->find($request), $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? null, $request->user());

        return $this->respond($visit, 'Visit cancelled.');
    }

    public function setTimes(Request $request, VisitEditor $editor)
    {
        $this->authorize('plan-visits');

        $visit = $this->find($request);
        $validated = $request->validate([
            'in' => ['required', 'date_format:H:i'],
            'out' => ['required', 'date_format:H:i', 'different:in'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->respond($editor->setTimes($visit, $validated['in'], $validated['out'], $validated['note'] ?? null, $request->user()), 'Visit times saved.');
    }

    /** Flagged visits waiting for a coordinator, oldest first. */
    public function reviewQueue(Request $request)
    {
        $this->authorize('plan-visits');

        $visits = Visit::with(['location', 'staff:id,name'])->where('review_status', 'needs_review')->orderBy('planned_start')->limit(200)->get();

        return response()->json([
            'status' => true,
            'message' => 'Visits to review',
            'data' => $visits->map(fn (Visit $v) => VisitPresenter::visit($v, $this->calendar->timezoneFor($v->staff)))->values(),
        ]);
    }

    public function review(Request $request, VisitEditor $editor)
    {
        $this->authorize('plan-visits');

        $visit = $this->find($request);
        $validated = $request->validate([
            'decision' => ['required', 'in:accept,reject'],
            'note' => ['nullable', 'required_if:decision,reject', 'string', 'max:1000'],
        ]);

        $visit = $editor->review($visit, $validated['decision'] === 'accept', $validated['note'] ?? null, $request->user());

        return $this->respond($visit, $validated['decision'] === 'accept' ? 'Visit accepted.' : 'Visit rejected — its time won’t count.');
    }

    private function find(Request $request): Visit
    {
        $request->validate(['id' => ['required', 'integer', ValidationRule::exists('visits', 'id')->where('tenant_id', $request->user()->tenant_id)]]);

        return Visit::with('staff')->findOrFail($request->id);
    }

    private function respond(Visit $visit, string $message)
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => VisitPresenter::visit($visit, $this->calendar->timezoneFor($visit->staff))]);
    }
}
