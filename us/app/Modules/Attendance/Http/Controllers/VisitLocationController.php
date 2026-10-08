<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Exceptions\AttendanceException;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Models\VisitLocation;
use App\Modules\Attendance\Models\VisitPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/** The clients' homes (or other places) staff visit — coordinators only. */
class VisitLocationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('plan-visits');

        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'include_inactive' => ['nullable', 'boolean']]);

        $data = VisitLocation::with('branch:id,name')
            ->when(! ($validated['include_inactive'] ?? false), fn ($q) => $q->where('is_active', true))
            ->when($validated['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'ilike', "%{$s}%")->orWhere('address', 'ilike', "%{$s}%")->orWhere('reference', 'ilike', "%{$s}%")))
            ->withCount(['visits as upcoming_visits_count' => fn ($q) => $q->whereDate('date', '>=', today())->where('status', 'planned')])
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'message' => 'Visit locations', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('plan-visits');

        $location = VisitLocation::create($this->validateLocation($request));

        return response()->json(['status' => true, 'message' => 'Location added.', 'data' => $location], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('plan-visits');

        $location = $this->find($request);
        $location->update($this->validateLocation($request) + $request->validate(['is_active' => ['nullable', 'boolean']]));

        return response()->json(['status' => true, 'message' => 'Location saved.', 'data' => $location]);
    }

    public function destroy(Request $request)
    {
        $this->authorize('plan-visits');

        $location = $this->find($request);

        if (Visit::where('visit_location_id', $location->id)->exists() || VisitPlan::where('visit_location_id', $location->id)->exists()) {
            throw new AttendanceException('This location has visits. Switch it off instead — past visits keep it.');
        }

        $location->delete();

        return response()->json(['status' => true, 'message' => 'Location deleted.']);
    }

    private function find(Request $request): VisitLocation
    {
        $request->validate(['id' => ['required', 'integer', ValidationRule::exists('visit_locations', 'id')->where('tenant_id', $request->user()->tenant_id)]]);

        return VisitLocation::findOrFail($request->id);
    }

    private function validateLocation(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'geofence_radius_m' => ['nullable', 'integer', 'between:25,5000'],
            'access_notes' => ['nullable', 'string', 'max:1000'],
            'branch_id' => ['nullable', 'integer', ValidationRule::exists('branches', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);
    }
}
