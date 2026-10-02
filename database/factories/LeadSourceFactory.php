<?php

namespace Database\Factories;

use App\Modules\Crm\Models\LeadSource;
use App\Modules\Platform\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadSource>
 */
class LeadSourceFactory extends Factory
{
    protected $model = LeadSource::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
