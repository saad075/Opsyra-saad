<?php

namespace Database\Factories;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadNote;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadNote>
 */
class LeadNoteFactory extends Factory
{
    protected $model = LeadNote::class;

    public function definition(): array
    {
        // See LeadStageChangeFactory et al. for why this is pinned to the
        // active TenantContext rather than left to Lead's own default.
        return [
            'tenant_id' => Tenant::factory(),
            'lead_id' => fn () => Lead::factory()->create(['tenant_id' => app(TenantContext::class)->id()])->id,
            'body' => fake()->sentence(),
        ];
    }
}
