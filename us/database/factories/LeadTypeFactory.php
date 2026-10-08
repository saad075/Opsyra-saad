<?php

namespace Database\Factories;

use App\Modules\Crm\Models\LeadType;
use App\Modules\Platform\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadType>
 */
class LeadTypeFactory extends Factory
{
    protected $model = LeadType::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
