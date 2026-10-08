<?php

namespace Database\Factories;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadPipeline;
use App\Modules\Platform\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        // Only meaningful inside an active TenantContext (every test using
        // this factory runs via SeedsTwoTenants' runAs, same assumption as
        // NoteFactory/TaskFactory from the earlier CRM attempt) — every
        // tenant gets a default pipeline seeded via CrmServiceProvider's
        // Tenant::created hook, so this is never actually null in practice.
        $defaultPipeline = fn () => LeadPipeline::where('is_default', true)->first();

        return [
            'tenant_id' => Tenant::factory(),
            'title' => fake()->catchPhrase(),
            'lead_value' => fake()->randomFloat(2, 1000, 100000),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->safeEmail(),
            'lead_pipeline_id' => fn () => $defaultPipeline()?->id,
            'lead_pipeline_stage_id' => fn () => $defaultPipeline()?->stages()->orderBy('sort_order')->first()?->id,
        ];
    }
}
