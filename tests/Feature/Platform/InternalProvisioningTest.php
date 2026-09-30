<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Modules\Auth\Jobs\SendSignInLink;
use App\Modules\Auth\Models\PasswordResetCode;
use App\Modules\Auth\Models\TenantAccessToken;
use App\Modules\Auth\Notifications\WelcomeAdminNotification;
use App\Modules\Platform\Models\OwnershipTransfer;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Platform\Support\MailHealth;
use App\Modules\Rbac\Support\DefaultRoles;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Mailer\Exception\TransportException;
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

    public function test_a_code_taken_by_a_different_company_is_a_conflict(): void
    {
        $this->signedPost($this->payload())->assertCreated();
        $this->signedPost($this->payload(['name' => 'Other', 'admin_email' => 'someone@other.test']))->assertStatus(409);
    }

    public function test_the_signup_succeeds_even_when_the_welcome_email_cannot_be_sent_yet(): void
    {
        Queue::fake();

        $this->signedPost($this->payload())->assertCreated();

        $this->assertTrue(Tenant::where('client_code', 'acme')->exists());
        Queue::assertPushed(SendSignInLink::class, fn ($job) => $job->kind === SendSignInLink::WELCOME && $job->tenantId !== null);
    }

    public function test_repeating_a_signup_that_already_got_through_succeeds_and_resends_the_welcome(): void
    {
        $this->signedPost($this->payload())->assertCreated();

        // Central lost the first answer and retries, with the email in different case.
        $this->signedPost($this->payload(['admin_email' => 'ADA@acme.test']))
            ->assertOk()
            ->assertJsonPath('data.already_existed', true);

        $this->assertSame(1, Tenant::where('client_code', 'acme')->count());
        $tenant = Tenant::where('client_code', 'acme')->firstOrFail();
        $admin = app(TenantContext::class)->runAs($tenant->id, fn () => User::where('email', 'ada@acme.test')->firstOrFail());
        Notification::assertSentToTimes($admin, WelcomeAdminNotification::class, 2);

        // Only the latest link works.
        $live = app(TenantContext::class)->runAs($tenant->id, fn () => PasswordResetCode::where('user_id', $admin->id)->whereNull('used_at')->count());
        $this->assertSame(1, $live);
    }

    public function test_central_can_resend_the_welcome_email(): void
    {
        $this->signedPost($this->payload())->assertCreated();

        $this->signedPost(['admin_email' => 'ada@acme.test'], path: '/api/internal/tenants/acme/welcome')->assertStatus(202);
        $this->signedPost(['admin_email' => 'nobody@acme.test'], path: '/api/internal/tenants/acme/welcome')->assertNotFound();
        $this->postJson('/api/internal/tenants/acme/welcome', ['admin_email' => 'ada@acme.test'])->assertUnauthorized();

        $tenant = Tenant::where('client_code', 'acme')->firstOrFail();
        $admin = app(TenantContext::class)->runAs($tenant->id, fn () => User::where('email', 'ada@acme.test')->firstOrFail());
        Notification::assertSentToTimes($admin, WelcomeAdminNotification::class, 2);
    }

    public function test_usage_reports_whether_email_is_getting_out(): void
    {
        Cache::forget('platform.mail_health');
        $this->assertFalse($this->signedPost([], path: '/api/internal/usage')->json('mail.failing'));

        report(new TransportException('Expected response code "250" but got code "421": Not enough credit.'));

        $mail = $this->signedPost([], path: '/api/internal/usage')->json('mail');
        $this->assertTrue($mail['failing']);
        $this->assertStringContainsString('Not enough credit', $mail['last_error']);

        $this->travel(1)->seconds();
        MailHealth::recordSent();

        $this->assertFalse($this->signedPost([], path: '/api/internal/usage')->json('mail.failing'));
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
        $this->assertSame(['client_code' => 'acme', 'active_employees' => 1, 'owner' => ['name' => 'Ada Admin', 'email' => 'ada@acme.test']], $acme);
        $this->assertSame(['failing', 'last_sent_at', 'last_failed_at', 'last_error'], array_keys($response->json('mail')));
    }

    public function test_opsyra_support_can_reassign_the_account_owner(): void
    {
        $this->signedPost($this->payload())->assertCreated();
        $tenant = Tenant::where('client_code', 'acme')->firstOrFail();
        $bob = app(TenantContext::class)->runAs($tenant->id, fn () => User::factory()->for($tenant)->create(['email' => 'bob@acme.test', 'name' => 'Bob', 'status' => 'active']));

        $body = ['email' => 'BOB@acme.test', 'reason' => 'Ada left without handing on', 'by' => 'Kashif'];
        $this->postJson('/api/internal/tenants/acme/owner', $body)->assertUnauthorized();
        $this->signedPost(['email' => 'nobody@acme.test'] + $body, path: '/api/internal/tenants/acme/owner')->assertNotFound();

        $this->signedPost($body, path: '/api/internal/tenants/acme/owner')
            ->assertOk()
            ->assertJsonPath('data.owner', ['name' => 'Bob', 'email' => 'bob@acme.test']);

        $this->assertSame($bob->id, $tenant->fresh()->owner_user_id);
        $transfer = app(TenantContext::class)->runAs($tenant->id, fn () => OwnershipTransfer::sole());
        $this->assertNull($transfer->by_user_id);
        $this->assertSame('Opsyra support (Kashif)', $transfer->by_label);
        $this->assertTrue(app(TenantContext::class)->runAs($tenant->id, fn () => $bob->fresh()->hasRole(DefaultRoles::ADMINISTRATOR)));
    }

    public function test_subscription_updates_need_a_valid_signature_too(): void
    {
        $this->postJson('/api/internal/tenants/acme/subscription', ['status' => 'active'])->assertUnauthorized();
        $this->postJson('/api/internal/usage', [])->assertUnauthorized();
    }
}
