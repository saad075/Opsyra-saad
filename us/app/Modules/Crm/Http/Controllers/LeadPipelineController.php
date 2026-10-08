<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadPipeline;
use App\Modules\Crm\Models\LeadPipelineStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule as ValidationRule;

class LeadPipelineController extends Controller
{
    public function index(Request $request)
    {
        $data = LeadPipeline::with('stages')->orderBy('name')->get();

        return response()->json(['status' => true, 'message' => 'Lead pipelines', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('lead_pipelines')->where('tenant_id', $tenantId)],
            'rotten_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $pipeline = DB::transaction(function () use ($validated) {
            if ($validated['is_default'] ?? false) {
                LeadPipeline::where('is_default', true)->update(['is_default' => false]);
            }

            return LeadPipeline::create([
                'name' => $validated['name'],
                'rotten_days' => $validated['rotten_days'] ?? 30,
                'is_default' => $validated['is_default'] ?? false,
            ]);
        });

        return response()->json(['status' => true, 'message' => 'Pipeline created.', 'data' => $pipeline], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_pipelines', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('lead_pipelines')->where('tenant_id', $tenantId)->ignore($request->id)],
            'rotten_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $pipeline = LeadPipeline::findOrFail($validated['id']);

        DB::transaction(function () use ($pipeline, $validated) {
            if (($validated['is_default'] ?? false) && ! $pipeline->is_default) {
                LeadPipeline::where('is_default', true)->update(['is_default' => false]);
            }

            $pipeline->update([
                'name' => $validated['name'],
                'rotten_days' => $validated['rotten_days'] ?? $pipeline->rotten_days,
                'is_default' => $validated['is_default'] ?? $pipeline->is_default,
            ]);
        });

        return response()->json(['status' => true, 'message' => 'Pipeline updated.', 'data' => $pipeline]);
    }

    /** The last remaining pipeline, and one with leads still in it, can't be deleted. */
    public function destroy(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_pipelines', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $pipeline = LeadPipeline::findOrFail($validated['id']);

        if (LeadPipeline::count() <= 1) {
            return response()->json(['status' => false, 'message' => 'A company needs at least one pipeline.'], 409);
        }

        if (Lead::where('lead_pipeline_id', $pipeline->id)->exists()) {
            return response()->json(['status' => false, 'message' => 'This pipeline still has leads in it. Move them first.'], 409);
        }

        $pipeline->delete();

        return response()->json(['status' => true, 'message' => 'Pipeline deleted.']);
    }

    public function storeStage(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'lead_pipeline_id' => ['required', 'integer', ValidationRule::exists('lead_pipelines', 'id')->where('tenant_id', $tenantId)],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', ValidationRule::unique('lead_pipeline_stages')->where('lead_pipeline_id', $request->lead_pipeline_id)],
            'name' => ['required', 'string', 'max:255', ValidationRule::unique('lead_pipeline_stages', 'name')->where('lead_pipeline_id', $request->lead_pipeline_id)],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $stage = LeadPipelineStage::create([
            'lead_pipeline_id' => $validated['lead_pipeline_id'],
            'code' => $validated['code'],
            'name' => $validated['name'],
            'probability' => $validated['probability'] ?? 0,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json(['status' => true, 'message' => 'Stage created.', 'data' => $stage], 201);
    }

    public function updateStage(Request $request)
    {
        $this->authorize('manage-leads');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_pipeline_stages', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $stage = LeadPipelineStage::findOrFail($validated['id']);

        $request->validate([
            'name' => [ValidationRule::unique('lead_pipeline_stages', 'name')->where('lead_pipeline_id', $stage->lead_pipeline_id)->ignore($stage->id)],
        ]);

        $stage->update([
            'name' => $validated['name'],
            'probability' => $validated['probability'] ?? $stage->probability,
            'sort_order' => $validated['sort_order'] ?? $stage->sort_order,
        ]);

        return response()->json(['status' => true, 'message' => 'Stage updated.', 'data' => $stage]);
    }

    /** code is never editable once created — it's the identity business logic (won/lost) relies on, not display text. */
    public function destroyStage(Request $request)
    {
        $this->authorize('manage-leads');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('lead_pipeline_stages', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $stage = LeadPipelineStage::findOrFail($validated['id']);

        if (LeadPipelineStage::where('lead_pipeline_id', $stage->lead_pipeline_id)->count() <= 1) {
            return response()->json(['status' => false, 'message' => 'A pipeline needs at least one stage.'], 409);
        }

        if (Lead::where('lead_pipeline_stage_id', $stage->id)->exists()) {
            return response()->json(['status' => false, 'message' => 'This stage still has leads in it. Move them first.'], 409);
        }

        $stage->delete();

        return response()->json(['status' => true, 'message' => 'Stage deleted.']);
    }
}
