<?php

namespace Database\Factories;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadFile;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadFile>
 */
class LeadFileFactory extends Factory
{
    protected $model = LeadFile::class;

    public function definition(): array
    {
        // `path` doesn't point at a real stored file by default — tests
        // that exercise download()/deletion's disk cleanup use
        // Storage::fake('local') and write the file themselves.
        return [
            'tenant_id' => Tenant::factory(),
            'lead_id' => fn () => Lead::factory()->create(['tenant_id' => app(TenantContext::class)->id()])->id,
            'original_name' => fake()->word().'.pdf',
            'path' => 'leads/test/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1000, 500000),
        ];
    }
}
