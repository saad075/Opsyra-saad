<?php

namespace Database\Factories;

use App\Modules\Crm\Models\LeadPipeline;
use App\Modules\Crm\Models\LeadPipelineStage;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadPipelineStage>
 */
class LeadPipelineStageFactory extends Factory
{
    protected $model = LeadPipelineStage::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            // LeadPipelineFactory's own 'tenant_id' default would create an
            // unrelated tenant here — pin it to the active context instead.
            // See the identical note on NoteFactory/TaskFactory from the
            // email-uniqueness and first CRM attempt's work.
            'lead_pipeline_id' => fn () => LeadPipeline::factory()->create([
                'tenant_id' => app(TenantContext::class)->id(),
            ])->id,
            'code' => fake()->unique()->regexify('[a-z]{8}'),
            'name' => fake()->unique()->words(2, true),
            'probability' => fake()->numberBetween(0, 100),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function won(): static
    {
        return $this->state(['code' => 'won', 'probability' => 100]);
    }

    public function lost(): static
    {
        return $this->state(['code' => 'lost', 'probability' => 0]);
    }
}
