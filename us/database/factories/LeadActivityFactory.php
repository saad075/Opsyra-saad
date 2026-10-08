<?php

namespace Database\Factories;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadActivity;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadActivity>
 */
class LeadActivityFactory extends Factory
{
    protected $model = LeadActivity::class;

    public function definition(): array
    {
        // See LeadNoteFactory et al. for why this is pinned to the active
        // TenantContext rather than left to Lead's own default.
        return [
            'tenant_id' => Tenant::factory(),
            'lead_id' => fn () => Lead::factory()->create(['tenant_id' => app(TenantContext::class)->id()])->id,
            'type' => fake()->randomElement(LeadActivity::TYPES),
            'title' => fake()->sentence(4),
        ];
    }
}
