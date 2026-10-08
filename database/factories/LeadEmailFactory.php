<?php

namespace Database\Factories;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadEmail;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadEmail>
 */
class LeadEmailFactory extends Factory
{
    protected $model = LeadEmail::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'lead_id' => fn () => Lead::factory()->create(['tenant_id' => app(TenantContext::class)->id()])->id,
            'to_email' => fake()->safeEmail(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
        ];
    }
}
