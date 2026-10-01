<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Auth\Models\LoginLog;
use App\Modules\Auth\Models\PasswordResetCode;
use App\Modules\Auth\Notifications\PasswordResetNotification;
use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Platform\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\AssertsTenantIsolation;
use Tests\TestCase;

/**
 * Deliberately never uses actingAs(): that shortcut skips Sanctum's token
 * lookup, which is exactly the path that was broken under RLS. Every
 * request here goes through a real bearer token.
 */
class AuthTest extends TestCase
{
    use AssertsTenantIsolation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    protected function login(User $user, string $password = 'password', ?string $clientCode = null)
    {
        return $this->postJson('/api/auth/login', [
            'client_code' => $clientCode ?? $user->tenant->client_code,
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'test-phone',
        ]);
    }

    /**
     * A fresh request with the token only. forgetGuards() stops the test
     * client reusing the previous request's resolved user.
     */
    protected function withBearer(string $token)
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_a_real_token_authenticates_under_row_level_security(): void
    {
        $token = $this->login($this->userA)->assertOk()->json('data.token');

        $this->withBearer($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $this->userA->id)
            ->assertJsonPath('data.company.client_code', $this->tenantA->client_code);

        // And tenant-scoped data comes back for the token's own tenant only.
        $names = collect($this->withBearer($token)->getJson('/api/departure-reasons/list')->assertOk()->json('data'));
        $ownIds = $this->tenantContext()->runAs($this->tenantA->id, fn () => DepartureReason::pluck('id'));
        $this->assertEqualsCanonicalizing($ownIds->all(), $names->pluck('id')->all());
    }

    public function test_wrong_password_unknown_email_and_unknown_company_all_look_the_same(): void
    {
        $messages = [
            $this->login($this->userA, 'wrong')->assertUnprocessable()->json('errors.email.0'),
            $this->postJson('/api/auth/login', ['client_code' => $this->tenantA->client_code, 'email' => 'nobody@x.test', 'password' => 'x'])->assertUnprocessable()->json('errors.email.0'),
            $this->login($this->userA, 'password', 'no-such-company')->assertUnprocessable()->json('errors.email.0'),
        ];

        $this->assertCount(1, array_unique($messages));
    }

    /**
     * Until 2026-10-01 this proved the opposite: that two tenants could each
     * own the same email, disambiguated by client_code at login. Email is
     * now globally unique across the deployment (see
     * Tests\Feature\Tenancy\GlobalEmailUniquenessTest and docs/decisions.md),
     * so that scenario can no longer arise — proven here at the point it
     * would have mattered for login, not just at the model level.
     */
    public function test_the_same_email_cannot_be_reused_by_another_company(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->tenantContext()->runAs($this->tenantB->id, fn () => $this->userB->forceFill(['email' => $this->userA->email])->save());
    }

    // SLMS360 said "account not active" to anyone who knew an email.
    public function test_an_inactive_account_is_only_revealed_after_the_right_password(): void
    {
        $this->tenantContext()->runAs($this->tenantA->id, fn () => $this->userA->update(['status' => 'offboarded']));

        $this->login($this->userA, 'wrong')->assertUnprocessable();
        $this->login($this->userA)->assertForbidden();
    }

    public function test_repeated_failures_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login($this->userA, 'wrong')->assertUnprocessable();
        }

        $this->login($this->userA)->assertStatus(429);
    }

    public function test_a_suspended_company_cannot_log_in(): void
    {
        $this->tenantA->update(['status' => 'suspended']);

        $this->login($this->userA)->assertForbidden();
    }

    public function test_a_token_stops_working_once_the_account_is_offboarded(): void
    {
        $token = $this->login($this->userA)->json('data.token');

        // Offboarding normally revokes tokens too; this proves the
        // middleware stops a token that somehow survived.
        $this->tenantContext()->runAs($this->tenantA->id, fn () => User::whereKey($this->userA->id)->update(['status' => 'offboarded']));

        $this->withBearer($token)->getJson('/api/departure-reasons/list')->assertForbidden();
    }

    public function test_logout_revokes_the_token(): void
    {
        $token = $this->login($this->userA)->json('data.token');

        $this->withBearer($token)->postJson('/api/auth/logout')->assertOk();
        $this->withBearer($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_a_provisioned_admin_must_change_their_temporary_password_first(): void
    {
        ['admin' => $admin, 'password' => $temporary] = app(TenantProvisioner::class)->provision([
            'name' => 'Acme', 'client_code' => 'acme', 'admin_name' => 'Ada', 'admin_email' => 'ada@acme.test',
        ]);

        $login = $this->login($admin, $temporary, 'acme')->assertOk()->assertJsonPath('data.must_change_password', true);
        $token = $login->json('data.token');
        $otherDevice = $this->login($admin, $temporary, 'acme')->json('data.token');

        $this->withBearer($token)->getJson('/api/setup-wizard/status')
            ->assertForbidden()
            ->assertJsonPath('password_change_required', true);

        $this->withBearer($token)->postJson('/api/auth/change-password', [
            'current_password' => $temporary,
            'password' => 'a-new-passw0rd',
            'password_confirmation' => 'a-new-passw0rd',
        ])->assertOk();

        $this->withBearer($token)->getJson('/api/setup-wizard/status')->assertOk();
        $this->withBearer($otherDevice)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_password_reset_works_once_and_signs_out_old_sessions(): void
    {
        Notification::fake();

        $oldToken = $this->login($this->userA)->json('data.token');

        $this->postJson('/api/auth/forgot-password', ['client_code' => $this->tenantA->client_code, 'email' => $this->userA->email])->assertOk();

        $emailed = null;
        Notification::assertSentTo($this->userA, PasswordResetNotification::class, function ($n) use (&$emailed) {
            $emailed = $n->token;

            return true;
        });

        $reset = fn () => $this->postJson('/api/auth/reset-password', [
            'client_code' => $this->tenantA->client_code,
            'email' => $this->userA->email,
            'token' => $emailed,
            'password' => 'brand-new-pass1',
            'password_confirmation' => 'brand-new-pass1',
        ]);

        $reset()->assertOk();
        $reset()->assertUnprocessable();

        $this->login($this->userA, 'brand-new-pass1')->assertOk();
        $this->withBearer($oldToken)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_forgot_password_never_reveals_whether_an_account_exists(): void
    {
        Notification::fake();

        $known = $this->postJson('/api/auth/forgot-password', ['client_code' => $this->tenantA->client_code, 'email' => $this->userA->email])->json('message');
        $unknown = $this->postJson('/api/auth/forgot-password', ['client_code' => $this->tenantA->client_code, 'email' => 'nobody@x.test'])->json('message');

        $this->assertSame($known, $unknown);
        Notification::assertSentTimes(PasswordResetNotification::class, 1);
    }

    public function test_logins_are_recorded_and_only_visible_with_permission(): void
    {
        $token = $this->login($this->userA)->json('data.token');

        $this->tenantContext()->runAs($this->tenantA->id, fn () => $this->assertSame(1, LoginLog::where('user_id', $this->userA->id)->count()));

        $this->withBearer($token)->getJson('/api/login-logs/list')->assertForbidden();

        $this->tenantContext()->runAs($this->tenantA->id, fn () => $this->userA->givePermissionTo('HRMS-ViewLoginLogs'));
        $this->withBearer($token)->getJson('/api/login-logs/list')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_login_logs_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(LoginLog::class, fn () => LoginLog::create(['user_id' => $this->userB->id, 'login_at' => now()]));
    }

    public function test_password_reset_codes_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(PasswordResetCode::class, fn () => PasswordResetCode::create([
            'user_id' => $this->userB->id, 'token_hash' => str_repeat('a', 64), 'expires_at' => now()->addHour(),
        ]));
    }
}
