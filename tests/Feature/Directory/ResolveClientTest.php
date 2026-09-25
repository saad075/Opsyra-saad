<?php

namespace Tests\Feature\Directory;

use App\Modules\Directory\Models\ClientDirectoryEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResolveClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_a_known_active_client_code(): void
    {
        ClientDirectoryEntry::create([
            'client_code' => 'acme-uk',
            'country' => 'uk',
            'endpoint_url' => 'https://uk.opsyra.example.com',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/resolve', ['code' => 'acme-uk']);

        $response->assertOk();
        $response->assertJson([
            'country' => 'uk',
            'endpoint' => 'https://uk.opsyra.example.com',
        ]);
    }

    public function test_it_rejects_an_unknown_client_code(): void
    {
        $response = $this->postJson('/api/resolve', ['code' => 'does-not-exist']);

        $response->assertNotFound();
    }

    public function test_it_rejects_an_inactive_client_code(): void
    {
        ClientDirectoryEntry::create([
            'client_code' => 'suspended-client',
            'country' => 'us',
            'endpoint_url' => 'https://us.opsyra.example.com',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/resolve', ['code' => 'suspended-client']);

        $response->assertNotFound();
    }

    public function test_it_stores_no_personal_data(): void
    {
        $this->assertEqualsCanonicalizing(
            ['id', 'client_code', 'country', 'endpoint_url', 'is_active', 'created_at', 'updated_at'],
            Schema::getColumnListing('client_directory')
        );
    }
}
