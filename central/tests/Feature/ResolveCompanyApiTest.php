<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveCompanyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['opsyra.countries.us.url' => 'https://us.opsyra.test/']);
    }

    private function company(string $status): Company
    {
        return Company::create([
            'client_code' => 'acme', 'name' => 'Acme', 'country' => 'us', 'status' => $status,
            'admin_name' => 'A', 'admin_email' => 'a@acme.test',
        ]);
    }

    public function test_the_app_finds_its_country_server_from_the_company_code(): void
    {
        $this->company('trial');

        $this->postJson('/api/resolve', ['client_code' => ' ACME '])
            ->assertOk()
            ->assertExactJson(['status' => true, 'data' => ['client_code' => 'acme', 'country' => 'us', 'api_base_url' => 'https://us.opsyra.test/api']]);
    }

    public function test_unknown_or_unreachable_companies_are_refused(): void
    {
        $this->postJson('/api/resolve', ['client_code' => 'nope'])->assertNotFound();

        $this->company('suspended');
        $this->postJson('/api/resolve', ['client_code' => 'acme'])->assertForbidden();

        $this->postJson('/api/resolve', [])->assertUnprocessable();
    }
}
