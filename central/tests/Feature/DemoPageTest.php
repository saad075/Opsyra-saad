<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_page_links_straight_into_the_demo_sign_in(): void
    {
        config(['opsyra.countries.us.url' => 'https://us.opsyra.test']);

        $this->get('/demo')
            ->assertOk()
            ->assertSee('https://us.opsyra.test/login?client_code=demo&amp;demo=1', false)
            ->assertSee(config('opsyra.demo.email'))
            ->assertSee(config('opsyra.demo.password'));
    }

    public function test_the_demo_is_registered_so_company_code_login_works_but_isnt_revenue(): void
    {
        $demo = Company::where('client_code', 'demo')->firstOrFail();

        $this->assertTrue($demo->is_demo);
        $this->post('/login', ['client_code' => 'demo'])->assertRedirectContains('/login?client_code=demo');
    }
}
