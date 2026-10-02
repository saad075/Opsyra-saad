<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * A per-tenant configurable list, same shape as HR's DepartureReason.
 * Listing is open to any authenticated staff member (the lead form needs
 * it); changes need manage-leads.
 */
class LeadSourceController extends Controller
{
    public function index(Request $request)
    {
        $data = LeadSource::query()
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
            ->orderBy('sequence')
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'message' => 'Lead sources', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('lead_sources')->where('tenant_id', $request->user()->tenant_id)],
            'sequence' => ['nullable', 'integer', 'min:0'],
        ]);

        $source = LeadSource::create([
            'name' => $validated['name'],
            'sequence' => $validated['sequence'] ?? 10,
        ]);

        return response()->json(['status' => true, 'message' => 'Lead source created.', 'data' => $source], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_sources', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('lead_sources')->where('tenant_id', $tenantId)->ignore($request->id)],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $source = LeadSource::findOrFail($validated['id']);

        $source->update([
            'name' => $validated['name'],
            'sequence' => $validated['sequence'] ?? $source->sequence,
            'is_active' => $validated['is_active'] ?? $source->is_active,
        ]);

        return response()->json(['status' => true, 'message' => 'Lead source updated.', 'data' => $source]);
    }

    /** System sources, and any source already used on a lead, can only be deactivated. */
    public function destroy(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_sources', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $source = LeadSource::findOrFail($validated['id']);

        if ($source->is_system || Lead::where('lead_source_id', $source->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'This source is built in or already in use. Deactivate it instead.',
            ], 409);
        }

        $source->delete();

        return response()->json(['status' => true, 'message' => 'Lead source deleted.']);
    }
}
