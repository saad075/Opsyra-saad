<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Modules\Auth\Models\TenantAccessToken;
use App\Modules\Auth\Notifications\WelcomeAdminNotification;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InternalProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-central-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.central.secret' => self::SECRET, 'app.country' => 'us']);
        Notification::fake();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Acme Ltd',
            'client_code' => 'acme',
            'country' => 'us',
            'admin_name' => 'Ada Admin',
            'admin_email' => 'ada@acme.test',
            'trial_ends_at' => now()->addDays(14)->toIso8601String(),
            'plan_code' => 'plus',
            'features' => ['people', 'payroll'],
        ];
    }

    /** Signs exactly as Opsyra Central does. */
    private function signedPost(array $payload, ?string $secret = self::SECRET, ?int $timestamp = null, string $path = '/api/internal/tenants')
    {
        $body = json_encode($payload);
        $timestamp ??= time();

        return $this->call('POST', $path, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_OPSYRA_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_OPSYRA_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, $secret),
        ], $body);
    }

    public function test_central_can_create_a_trial_company_and_the_admin_is_emailed_a_set_password_link(): void
    {
        $this->signedPost($this->payload())->assertCreated()->assertJsonPath('data.client_code', 'acme');

        $tenant = Tenant::where('client_code', 'acme')->firstOrFail();
        $this->assertSame('trial', $tenant->status);
        $this->assertSame('plus', $tenant->plan_code);
        $this->assertSame(['people', 'payroll'], $tenant->features);
        $this->assertNotNull($tenant->trial_ends_at);

        $admin = app(TenantContext::class)->runAs($tenant->id, fn () => User::where('email', 'ada@acme.test')->firstOrFail());
        Notification::assertSentTo($admin, WelcomeAdminNotification::class, fn ($n) => strlen($n->token) === 64);
    }

    public function test_an_unsigned_or_wrongly_signed_request_is_refused(): void
    {
        $this->postJson('/api/internal/tenants', $this->payload())->assertUnauthorized();
        $this->signedPost($this->payload(), 'wrong-secret')->assertUnauthorized();

        $this->assertFalse(Tenant::where('client_code', 'acme')->exists());
    }

    public function test_an_old_request_cannot_be_replayed(): void
    {
        $this->signedPost($this->payload(), timestamp: time() - 600)->assertUnauthorized();
    }

    public function test_a_tampered_body_fails_the_signature(): void
    {
        $body = json_encode($this->payload());
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, self::SECRET);

        $this->call('POST', '/api/internal/tenants', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_OPSYRA_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_OPSYRA_SIGNATURE' => $signature,
        ], json_encode($this->payload(['admin_email' => 'attacker@evil.test'])))->assertUnauthorized();
    }

    public function test_a_code_already_in_use_is_a_conflict(): void
    {
        $this->signedPost($this->payload())->assertCreated();
        $this->signedPost($this->payload(['name' => 'Other']))->assertStatus(409);
    }

    public function test_without_a_configured_secret_the_endpoint_is_closed(): void
    {
        config(['services.central.secret' => null]);

        $this->signedPost($this->payload())->assertStatus(503);
    }

    public function test_central_can_suspend_a_company_which_signs_everyone_out(): void
    {
        $this->signedPost($this->payload())->assertCreated();
        $tenant = Tenant::where('client_code', 'acme')->firstOrFail();
        $admin = app(TenantContext::class)->runAs($tenant->id, fn () => User::firstOrFail());
        app(TenantContext::class)->runAs($tenant->id, fn () => $admin->createToken('web'));

        $this->signedPost(['status' => 'suspended', 'plan_code' => 'plus', 'features' => ['people']], path: '/api/internal/tenants/acme/subscription')
            ->assertOk();

        $this->assertSame('suspended', $tenant->fresh()->status);
        $this->assertSame(0, TenantAccessToken::where('tenant_id', $tenant->id)->count());
    }

    public function test_central_can_extend_a_trial(): void
    {
        $this->signedPost($this->payload())->assertCreated();
        $newEnd = now()->addDays(30)->startOfSecond();

        $this->signedPost(['status' => 'trial', 'trial_ends_at' => $newEnd->toIso8601String()], path: '/api/internal/tenants/acme/subscription')->assertOk();

        $this->assertTrue(Tenant::where('client_code', 'acme')->value('trial_ends_at') !== null);
        $this->assertSame($newEnd->toDateString(), Tenant::where('client_code', 'acme')->first()->trial_ends_at->toDateString());
    }

    public function test_usage_reports_only_counts_per_company(): void
    {
        $this->signedPost($this->payload())->assertCreated();

        $response = $this->signedPost([], path: '/api/internal/usage')->assertOk();

        $acme = collect($response->json('data'))->firstWhere('client_code', 'acme');
        $this->assertSame(['client_code' => 'acme', 'active_employees' => 1], $acme);
    }

    public function test_subscription_updates_need_a_valid_signature_too(): void
    {
        $this->postJson('/api/internal/tenants/acme/subscription', ['status' => 'active'])->assertUnauthorized();
        $this->postJson('/api/internal/usage', [])->assertUnauthorized();
    }
}
