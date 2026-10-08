<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Hr\Models\StaffOffboardRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * New — no SLMS360 equivalent (reasons were free text there). Each tenant
 * manages its own list; see DepartureReason::defaultsFor() for what a new
 * tenant starts with. Listing is open to any authenticated staff member
 * (the offboarding form needs it); changes need manage-hr.
 */
class DepartureReasonController extends Controller
{
    public function index(Request $request)
    {
        $data = DepartureReason::query()
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
            ->orderBy('sequence')
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'message' => 'Departure reasons', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-hr');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('departure_reasons')->where('tenant_id', $request->user()->tenant_id)],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'requires_note' => ['nullable', 'boolean'],
        ]);

        $reason = DepartureReason::create([
            'name' => $validated['name'],
            'sequence' => $validated['sequence'] ?? 10,
            'requires_note' => $validated['requires_note'] ?? false,
        ]);

        return response()->json(['status' => true, 'message' => 'Departure reason created.', 'data' => $reason], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-hr');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('departure_reasons', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('departure_reasons')->where('tenant_id', $tenantId)->ignore($request->id)],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'requires_note' => ['nullable', 'boolean'],
        ]);

        $reason = DepartureReason::findOrFail($validated['id']);

        $reason->update([
            'name' => $validated['name'],
            'sequence' => $validated['sequence'] ?? $reason->sequence,
            'is_active' => $validated['is_active'] ?? $reason->is_active,
            'requires_note' => $validated['requires_note'] ?? $reason->requires_note,
        ]);

        return response()->json(['status' => true, 'message' => 'Departure reason updated.', 'data' => $reason]);
    }

    /**
     * System reasons, and any reason already used on an exit, can only be
     * deactivated — deleting them would orphan historical turnover reporting.
     */
    public function destroy(Request $request)
    {
        $this->authorize('manage-hr');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('departure_reasons', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $reason = DepartureReason::findOrFail($validated['id']);

        if ($reason->is_system || StaffOffboardRequest::where('departure_reason_id', $reason->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'This reason is built in or already in use. Deactivate it instead.',
            ], 409);
        }

        $reason->delete();

        return response()->json(['status' => true, 'message' => 'Departure reason deleted.']);
    }
}
