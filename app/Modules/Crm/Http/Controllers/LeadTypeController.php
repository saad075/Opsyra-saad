<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/** Same shape and reasoning as LeadSourceController. */
class LeadTypeController extends Controller
{
    public function index(Request $request)
    {
        $data = LeadType::query()
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
            ->orderBy('sequence')
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'message' => 'Lead types', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('lead_types')->where('tenant_id', $request->user()->tenant_id)],
            'sequence' => ['nullable', 'integer', 'min:0'],
        ]);

        $type = LeadType::create([
            'name' => $validated['name'],
            'sequence' => $validated['sequence'] ?? 10,
        ]);

        return response()->json(['status' => true, 'message' => 'Lead type created.', 'data' => $type], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_types', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('lead_types')->where('tenant_id', $tenantId)->ignore($request->id)],
            'sequence' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $type = LeadType::findOrFail($validated['id']);

        $type->update([
            'name' => $validated['name'],
            'sequence' => $validated['sequence'] ?? $type->sequence,
            'is_active' => $validated['is_active'] ?? $type->is_active,
        ]);

        return response()->json(['status' => true, 'message' => 'Lead type updated.', 'data' => $type]);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_types', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $type = LeadType::findOrFail($validated['id']);

        if ($type->is_system || Lead::where('lead_type_id', $type->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'This type is built in or already in use. Deactivate it instead.',
            ], 409);
        }

        $type->delete();

        return response()->json(['status' => true, 'message' => 'Lead type deleted.']);
    }
}
