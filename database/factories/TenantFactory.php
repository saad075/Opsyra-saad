<?php

namespace Database\Factories;

use App\Modules\Platform\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'client_code' => strtolower(fake()->unique()->bothify('client-????##')),
            'name' => fake()->company(),
            'country' => 'us',
            'status' => 'active',
        ];
    }
}
