<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadPipeline;
use App\Modules\Crm\Models\LeadPipelineStage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $data = Lead::with(['source:id,name', 'type:id,name', 'pipeline:id,name', 'stage:id,name,code', 'owner:id,name'])
            ->when($request->filled('lead_pipeline_id'), fn ($q) => $q->where('lead_pipeline_id', $request->integer('lead_pipeline_id')))
            ->when($request->filled('lead_pipeline_stage_id'), fn ($q) => $q->where('lead_pipeline_stage_id', $request->integer('lead_pipeline_stage_id')))
            ->when($request->filled('status'), function ($q) use ($request) {
                match ($request->string('status')->toString()) {
                    'open' => $q->whereNull('status'),
                    'won' => $q->where('status', true),
                    'lost' => $q->where('status', false),
                    default => null,
                };
            })
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'ilike', '%'.$request->string('search').'%'))
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Leads',
            'data' => $data->map(fn (Lead $lead) => [...$lead->toArray(), 'is_rotten' => $lead->isRotten()]),
        ]);
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('leads', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $lead = Lead::with(['source:id,name', 'type:id,name', 'pipeline:id,name,rotten_days', 'stage:id,name,code,probability', 'owner:id,name'])
            ->findOrFail($validated['id']);

        return response()->json([
            'status' => true,
            'message' => 'Lead',
            'data' => [...$lead->toArray(), 'is_rotten' => $lead->isRotten()],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $this->validated($request);
        $lostReason = $validated['lost_reason'] ?? null;
        unset($validated['lost_reason'], $validated['lead_pipeline_id'], $validated['lead_pipeline_stage_id']);

        $pipeline = $request->filled('lead_pipeline_id')
            ? LeadPipeline::find($request->integer('lead_pipeline_id'))
            : LeadPipeline::where('is_default', true)->first();

        $stage = $request->filled('lead_pipeline_stage_id')
            ? LeadPipelineStage::find($request->integer('lead_pipeline_stage_id'))
            : $pipeline?->stages()->orderBy('sort_order')->first();

        $lead = Lead::create([
            ...$validated,
            'lead_pipeline_id' => $pipeline?->id,
            'lead_pipeline_stage_id' => $stage?->id,
            'created_by' => $request->user()->id,
        ]);

        // Only a seeded/custom pipeline whose first stage is itself won/lost
        // (unusual, but possible) needs this — the normal case leaves the
        // lead open on whatever stage it started on.
        if ($stage && ($stage->isWon() || $stage->isLost())) {
            $lead->moveToStage($stage, $lostReason);
        }

        return response()->json(['status' => true, 'message' => 'Lead created.', 'data' => $lead], 201);
    }

    /**
     * Never touches the stage/pipeline — see move() below, the only path
     * that changes them, so the won/lost side effects in
     * Lead::moveToStage() can't be bypassed by a plain field edit.
     */
    public function update(Request $request)
    {
        $this->authorize('manage-leads');

        $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('leads', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $lead = Lead::findOrFail($request->id);
        $validated = $this->validated($request);
        unset($validated['lead_pipeline_id'], $validated['lead_pipeline_stage_id'], $validated['lost_reason']);

        $lead->update($validated);

        return response()->json(['status' => true, 'message' => 'Lead updated.', 'data' => $lead]);
    }

    /**
     * The only way a lead's stage changes — keeps won/lost/reopen logic in
     * one place (Lead::moveToStage) rather than duplicated between a plain
     * field update and a dedicated action.
     */
    public function move(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('leads', 'id')->where('tenant_id', $tenantId)],
            'lead_pipeline_stage_id' => ['required', 'integer', ValidationRule::exists('lead_pipeline_stages', 'id')->where('tenant_id', $tenantId)],
            'lost_reason' => ['nullable', 'string'],
        ]);

        $lead = Lead::findOrFail($validated['id']);
        $stage = LeadPipelineStage::findOrFail($validated['lead_pipeline_stage_id']);

        if ($stage->isLost() && empty($validated['lost_reason'])) {
            return response()->json(['status' => false, 'message' => 'A reason is required to mark a lead as lost.', 'errors' => ['lost_reason' => ['A reason is required to mark a lead as lost.']]], 422);
        }

        $lead->moveToStage($stage, $validated['lost_reason'] ?? null);

        return response()->json(['status' => true, 'message' => 'Lead moved.', 'data' => $lead]);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('leads', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        Lead::findOrFail($validated['id'])->delete();

        return response()->json(['status' => true, 'message' => 'Lead deleted.']);
    }

    private function validated(Request $request): array
    {
        $tenantId = $request->user()->tenant_id;

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'lead_value' => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'lead_source_id' => ['nullable', ValidationRule::exists('lead_sources', 'id')->where('tenant_id', $tenantId)],
            'lead_type_id' => ['nullable', ValidationRule::exists('lead_types', 'id')->where('tenant_id', $tenantId)],
            'lead_pipeline_id' => ['nullable', ValidationRule::exists('lead_pipelines', 'id')->where('tenant_id', $tenantId)],
            'lead_pipeline_stage_id' => ['nullable', ValidationRule::exists('lead_pipeline_stages', 'id')->where('tenant_id', $tenantId)],
            'owner_id' => ['nullable', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'lost_reason' => ['nullable', 'string'],
        ]);
    }
}
