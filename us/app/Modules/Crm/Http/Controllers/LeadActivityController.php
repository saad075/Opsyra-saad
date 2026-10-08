<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\LeadActivity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

class LeadActivityController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'lead_id' => ['required', 'integer', ValidationRule::exists('leads', 'id')->where('tenant_id', $tenantId)],
            'type' => ['required', ValidationRule::in(LeadActivity::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $activity = LeadActivity::create([
            ...$validated,
            // Explicit, not left to the column's DB default — the
            // in-memory model after create() only reflects what's actually
            // passed in, not DB-applied defaults, so the JSON response
            // would otherwise show null instead of false.
            'is_done' => false,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['status' => true, 'message' => 'Activity logged.', 'data' => $activity->load('author:id,name')], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_activities', 'id')->where('tenant_id', $tenantId)],
            'type' => ['required', ValidationRule::in(LeadActivity::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
            'is_done' => ['nullable', 'boolean'],
        ]);

        $activity = LeadActivity::findOrFail($validated['id']);
        $activity->update([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'comment' => $validated['comment'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'is_done' => $validated['is_done'] ?? $activity->is_done,
        ]);

        return response()->json(['status' => true, 'message' => 'Activity updated.', 'data' => $activity]);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_activities', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        LeadActivity::findOrFail($validated['id'])->delete();

        return response()->json(['status' => true, 'message' => 'Activity deleted.']);
    }
}
