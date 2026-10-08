<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Models\VisitPlan;
use App\Modules\Attendance\Services\VisitScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Repeating visits — "Mrs Patel, Mon/Wed/Fri 08:00–09:00, Ama" — which
 * generate the dated visits two weeks ahead.
 */
class VisitPlanController extends Controller
{
    public function __construct(private VisitScheduler $scheduler) {}

    public function index(Request $request)
    {
        $this->authorize('plan-visits');

        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'visit_location_id' => ['nullable', 'integer', ValidationRule::exists('visit_locations', 'id')->where('tenant_id', $tenantId)],
            'user_id' => ['nullable', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        $data = VisitPlan::with(['location:id,name,address', 'staff:id,name'])
            ->when($validated['visit_location_id'] ?? null, fn ($q, $id) => $q->where('visit_location_id', $id))
            ->when($validated['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))
            ->orderBy('start_time')
            ->get();

        return response()->json(['status' => true, 'message' => 'Visit plans', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('plan-visits');

        $plan = DB::transaction(function () use ($request) {
            $plan = VisitPlan::create($this->validatePlan($request) + ['created_by' => $request->user()->id]);
            $this->scheduler->generateFor($plan->load(['staff', 'location']));

            return $plan;
        });

        return response()->json(['status' => true, 'message' => 'Visits planned for the next two weeks; more are added each day.', 'data' => $plan], 201);
    }

    /** Changing a plan replaces its future visits that haven't started or been changed by hand. */
    public function update(Request $request)
    {
        $this->authorize('plan-visits');

        $plan = $this->find($request);

        DB::transaction(function () use ($plan, $request) {
            $plan->update($this->validatePlan($request));
            $this->scheduler->refresh($plan->load('staff'));
        });

        return response()->json(['status' => true, 'message' => 'Plan saved. Upcoming visits updated.', 'data' => $plan->fresh()]);
    }

    /** Ending a plan: no more visits from today; ones already started or done stay. */
    public function destroy(Request $request)
    {
        $this->authorize('plan-visits');

        $plan = $this->find($request);

        DB::transaction(function () use ($plan) {
            Visit::where('visit_plan_id', $plan->id)->where('status', 'planned')->where('planned_start', '>', now())->delete();
            $plan->update(['ends_on' => today()->subDay()->max($plan->starts_on)->toDateString()]);
        });

        return response()->json(['status' => true, 'message' => 'Plan ended. Upcoming visits removed.']);
    }

    private function find(Request $request): VisitPlan
    {
        $request->validate(['id' => ['required', 'integer', ValidationRule::exists('visit_plans', 'id')->where('tenant_id', $request->user()->tenant_id)]]);

        return VisitPlan::findOrFail($request->id);
    }

    private function validatePlan(Request $request): array
    {
        $tenantId = $request->user()->tenant_id;

        return $request->validate([
            'visit_location_id' => ['required', 'integer', ValidationRule::exists('visit_locations', 'id')->where('tenant_id', $tenantId)->where('is_active', true)],
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)->whereIn('status', ['active', 'pending'])],
            'weekdays' => ['required', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
