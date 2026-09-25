<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GetStartedTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'shared-test-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'opsyra.countries.us.api_url' => 'https://us-api.opsyra.test',
            'opsyra.countries.us.secret' => self::SECRET,
            'opsyra.countries.om.api_url' => null,
            'opsyra.countries.om.secret' => null,
        ]);
    }

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'company_name' => 'Acme Ltd',
            'client_code' => 'Acme',
            'country' => 'us',
            'employees' => 25,
            'admin_name' => 'Ada Admin',
            'admin_email' => 'Ada@Acme.test',
            'terms' => '1',
        ];
    }

    public function test_signing_up_creates_the_company_on_its_country_with_a_14_day_trial(): void
    {
        Http::fake(['us-api.opsyra.test/*' => Http::response(['status' => true], 201)]);

        $this->post('/get-started', $this->form())->assertRedirect('/get-started/done');

        $company = Company::where('client_code', 'acme')->firstOrFail();
        $this->assertSame('trial', $company->status);
        $this->assertSame('ada@acme.test', $company->admin_email);
        $this->assertSame('plus', $company->plan->code, 'Default trial plan is the middle tier.');
        $this->assertSame(now()->addDays(14)->toDateString(), $company->trial_ends_at->toDateString());

        Http::assertSent(function (Request $request) {
            // The signature must verify exactly as the country side checks it.
            $expected = hash_hmac('sha256', $request->header('X-Opsyra-Timestamp')[0].'.'.$request->body(), self::SECRET);

            return $request->url() === 'https://us-api.opsyra.test/api/internal/tenants'
                && hash_equals($expected, $request->header('X-Opsyra-Signature')[0])
                && $request['client_code'] === 'acme'
                && in_array('payroll', $request['features'], true);
        });

        $this->get('/get-started/done')->assertOk()->assertSee('Your workspace is ready')->assertSee('acme');
    }

    public function test_if_the_country_deployment_fails_the_signup_is_kept_and_flagged(): void
    {
        Http::fake(['us-api.opsyra.test/*' => Http::response(['message' => 'Database unavailable'], 500)]);

        $this->post('/get-started', $this->form())->assertRedirect('/get-started/done');

        $company = Company::where('client_code', 'acme')->firstOrFail();
        $this->assertSame('provisioning_failed', $company->status);
        $this->assertStringContainsString('Database unavailable', $company->provision_error);

        $this->get('/get-started/done')->assertOk()->assertSee('finishing your setup');
    }

    public function test_a_taken_or_reserved_code_is_refused(): void
    {
        Http::fake();
        Company::create(['client_code' => 'acme', 'name' => 'Existing', 'country' => 'us', 'status' => 'trial', 'admin_name' => 'X', 'admin_email' => 'x@x.test']);

        $this->post('/get-started', $this->form())->assertSessionHasErrors('client_code');
        $this->post('/get-started', $this->form(['client_code' => 'admin']))->assertSessionHasErrors('client_code');
        $this->post('/get-started', $this->form(['client_code' => 'no spaces!']))->assertSessionHasErrors('client_code');

        Http::assertNothingSent();
    }

    public function test_the_live_code_check_answers_while_typing(): void
    {
        Company::create(['client_code' => 'acme', 'name' => 'Existing', 'country' => 'us', 'status' => 'trial', 'admin_name' => 'X', 'admin_email' => 'x@x.test']);

        $this->getJson('/get-started/check-code?code=acme')->assertJsonPath('available', false);
        $this->getJson('/get-started/check-code?code=globex')->assertJsonPath('available', true);
        $this->getJson('/get-started/check-code?code=demo')->assertJsonPath('available', false);
    }

    public function test_a_country_not_yet_connected_cannot_take_signups(): void
    {
        Http::fake();

        $this->post('/get-started', $this->form(['country' => 'om']))->assertSessionHasErrors('country');

        $this->assertFalse(Company::where('client_code', 'acme')->exists());
        Http::assertNothingSent();
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->post('/get-started', $this->form(['terms' => null]))->assertSessionHasErrors('terms');
    }

    public function test_the_confirmation_page_cannot_be_used_to_look_up_other_companies(): void
    {
        Company::create(['client_code' => 'acme', 'name' => 'Secret Co', 'country' => 'us', 'status' => 'trial', 'admin_name' => 'X', 'admin_email' => 'x@x.test']);

        $this->get('/get-started/done?code=acme')->assertRedirect('/get-started');
    }
}
