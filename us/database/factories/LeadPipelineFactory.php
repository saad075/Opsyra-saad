<?php

namespace Database\Factories;

use App\Modules\Crm\Models\LeadPipeline;
use App\Modules\Platform\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadPipeline>
 */
class LeadPipelineFactory extends Factory
{
    protected $model = LeadPipeline::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->words(2, true),
            'is_default' => false,
            'rotten_days' => 30,
        ];
    }
}
