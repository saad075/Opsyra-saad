<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function company(array $attributes = []): Company
    {
        return Company::create($attributes + [
            'client_code' => 'acme', 'name' => 'Acme', 'country' => 'us', 'status' => 'trial',
            'admin_name' => 'Ada', 'admin_email' => 'ada@acme.test',
        ]);
    }

    public function test_a_known_code_goes_to_its_countrys_sign_in_with_the_code_filled_in(): void
    {
        config(['opsyra.countries.us.url' => 'https://us.opsyra.test']);
        $this->company();

        $this->post('/login', ['client_code' => ' ACME '])
            ->assertRedirect('https://us.opsyra.test/login?client_code=acme');
    }

    public function test_an_unknown_code_is_refused_on_the_same_page(): void
    {
        $this->from('/login')->post('/login', ['client_code' => 'nobody'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('client_code');
    }

    public function test_a_suspended_company_is_told_so_rather_than_sent_on(): void
    {
        $this->company(['status' => 'suspended']);

        $this->from('/login')->post('/login', ['client_code' => 'acme'])->assertSessionHasErrors('client_code');
        $this->assertStringContainsString('suspended', session('errors')->first('client_code'));
    }

    public function test_a_company_still_being_set_up_cannot_be_reached_yet(): void
    {
        $this->company(['status' => 'provisioning']);

        $this->from('/login')->post('/login', ['client_code' => 'acme'])->assertSessionHasErrors('client_code');
    }
}
