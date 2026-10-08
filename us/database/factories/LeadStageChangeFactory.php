<?php

namespace Database\Factories;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadPipelineStage;
use App\Modules\Crm\Models\LeadStageChange;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadStageChange>
 */
class LeadStageChangeFactory extends Factory
{
    protected $model = LeadStageChange::class;

    public function definition(): array
    {
        // See NoteFactory/LeadFactory (and friends) from earlier CRM work
        // for why nested factories must be pinned to the active
        // TenantContext rather than left to their own 'tenant_id' default.
        return [
            'tenant_id' => Tenant::factory(),
            'lead_id' => fn () => Lead::factory()->create(['tenant_id' => app(TenantContext::class)->id()])->id,
            'to_lead_pipeline_stage_id' => fn (array $attrs) => LeadPipelineStage::where('tenant_id', app(TenantContext::class)->id())
                ->where('lead_pipeline_id', Lead::find($attrs['lead_id'])->lead_pipeline_id)
                ->inRandomOrder()
                ->value('id'),
        ];
    }
}
