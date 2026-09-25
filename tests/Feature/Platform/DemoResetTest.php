<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Modules\Platform\Demo\DemoCompanyBuilder;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

/**
 * The public demo company (demo:reset). Development machines running AVG
 * need the project folder excluded: its IDP.Generic heuristic flagged and
 * quarantined earlier versions of this file on 2026-09-25 (docs/status.md).
 */
class DemoResetTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.country' => 'us']);
        $this->travelTo('2026-09-25 10:00');
        $this->seedTwoTenants();
    }

    private function demoToken(): string
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/auth/login', [
            'client_code' => 'demo',
            'email' => DemoCompanyBuilder::ADMIN_EMAIL,
            'password' => DemoCompanyBuilder::ADMIN_PASSWORD,
        ])->assertOk()->json('data.token');
    }

    private function asDemo(string $token)
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_rebuilding_twice_leaves_one_clean_demo_and_other_companies_untouched(): void
    {
        $otherUsersBefore = app(TenantContext::class)->runAs($this->tenantA->id, fn () => User::count());

        $this->artisan('demo:reset')->assertSuccessful();
        $first = Tenant::where('client_code', 'demo')->value('id');

        $this->artisan('demo:reset')->assertSuccessful();
        $demos = Tenant::withTrashed()->where('client_code', 'demo')->get();

        $this->assertCount(1, $demos, 'Exactly one demo company after a rebuild.');
        $this->assertNotSame($first, $demos->first()->id, 'The old demo is gone, not reused.');
        $this->assertTrue($demos->first()->is_demo);
        $this->assertSame($otherUsersBefore, app(TenantContext::class)->runAs($this->tenantA->id, fn () => User::count()));
    }

    public function test_the_demo_is_full_enough_to_show_off_every_dashboard(): void
    {
        $this->artisan('demo:reset')->assertSuccessful();
        $token = $this->demoToken();

        $hr = $this->asDemo($token)->getJson('/api/hr/dashboard')->assertOk()->json('data');
        $this->assertGreaterThanOrEqual(20, $hr['headcount']['active']);
        $this->assertSame(1, $hr['approvals']['onboarding'], 'One new hire waiting for level 2.');
        $this->assertSame(1, $hr['approvals']['offboarding'], 'One leaver waiting for level 2.');
        $this->assertGreaterThanOrEqual(1, $hr['checklists']['overdue_tasks']);
        $this->assertCount(3, $hr['by_branch']);
        $this->assertNotEmpty($hr['upcoming_joiners']);
        $this->assertNotEmpty($hr['upcoming_leavers']);

        $pay = $this->asDemo($token)->getJson('/api/payroll/dashboard')->assertOk()->json('data');
        $this->assertCount(4, $pay['runs'], 'Three approved months plus this month.');
        $this->assertGreaterThan(0, $pay['awaiting']['drafts']);
        $this->assertGreaterThan(0, $pay['awaiting']['level_2']);
        $this->assertGreaterThan(0, $pay['awaiting']['cheques']);
        $this->assertSame(1, $pay['awaiting']['claims']);
        $this->assertLessThan($pay['coverage']['active_staff'], $pay['coverage']['with_bank_details']);

        $setup = $this->asDemo($token)->getJson('/api/setup-wizard/status')->assertOk()->json('data');
        $this->assertTrue($setup['is_complete'], 'The demo company has finished setting up.');

        $this->asDemo($token)->getJson('/api/auth/me')->assertJsonPath('data.company.is_demo', true);
    }

    public function test_the_shared_demo_account_stays_usable_for_the_next_visitor(): void
    {
        $this->artisan('demo:reset')->assertSuccessful();
        $token = $this->demoToken();
        $adminId = app(TenantContext::class)->runAs(
            Tenant::where('client_code', 'demo')->value('id'),
            fn () => User::where('email', DemoCompanyBuilder::ADMIN_EMAIL)->value('id'),
        );

        $this->asDemo($token)->postJson('/api/auth/change-password', [
            'current_password' => DemoCompanyBuilder::ADMIN_PASSWORD,
            'password' => 'another-pass-123',
            'password_confirmation' => 'another-pass-123',
        ])->assertForbidden()->assertJsonPath('demo_locked', true);

        $this->asDemo($token)->postJson('/api/roles/create', ['name' => 'Visitor role'])->assertForbidden();
        $this->asDemo($token)->postJson('/api/staff/offboard', ['id' => $adminId])->assertForbidden();

        // Visitors can still try things for real.
        $this->asDemo($token)->postJson('/api/branches/create', ['name' => 'Visitor branch'])->assertCreated();

        // And the shared sign-in still works afterwards.
        $this->demoToken();
    }

    public function test_forgot_password_never_emails_for_the_demo(): void
    {
        Notification::fake();
        $this->artisan('demo:reset')->assertSuccessful();

        $this->postJson('/api/auth/forgot-password', ['client_code' => 'demo', 'email' => DemoCompanyBuilder::ADMIN_EMAIL])->assertOk();

        Notification::assertNothingSent();
    }
}
