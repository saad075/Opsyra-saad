<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionDashboardTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'shared-test-secret';

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['opsyra.countries.us.api_url' => 'https://us-api.opsyra.test', 'opsyra.countries.us.secret' => self::SECRET]);
        $this->admin = User::factory()->create(['is_active' => true]);
    }

    private function company(array $attributes = []): Company
    {
        static $n = 0;
        $n++;

        return Company::create($attributes + [
            'client_code' => "co{$n}", 'name' => "Company {$n}", 'country' => 'us', 'status' => 'trial',
            'plan_id' => Plan::where('code', 'plus')->value('id'), 'trial_ends_at' => now()->addDays(10),
            'admin_name' => 'Admin', 'admin_email' => "admin{$n}@co.test", 'expected_employees' => 25,
        ]);
    }

    public function test_the_dashboard_summarises_companies_and_revenue_per_currency(): void
    {
        $this->company(['status' => 'active', 'active_employees' => 25]); // Plus US: 30 + 15 × 1.5 = 52.50
        $this->company(['status' => 'active', 'active_employees' => 10]); // 30.00
        $this->company(['status' => 'trial', 'trial_ends_at' => now()->addDays(3)]);
        $this->company(['status' => 'suspended']);
        $this->company(['status' => 'active', 'is_demo' => true, 'active_employees' => 500]); // excluded

        $this->actingAs($this->admin)->get('/admin')
            ->assertOk()
            ->assertSee('Subscriptions')
            ->assertSee('USD 82.50')   // revenue from the two paying companies only
            ->assertDontSee('USD 1,010'); // the demo company isn't revenue
    }

    public function test_trials_ending_soon_and_failed_setups_are_flagged(): void
    {
        $ending = $this->company(['name' => 'Ending Soon Ltd', 'trial_ends_at' => now()->addDays(2)]);
        $failed = $this->company(['name' => 'Broken Setup Ltd', 'status' => 'provisioning_failed', 'provision_error' => 'boom']);

        $this->actingAs($this->admin)->get('/admin')->assertSee('Ending Soon Ltd')->assertSee('Broken Setup Ltd')->assertSee('Needs attention');
    }

    public function test_extending_a_trial_saves_it_and_pushes_it_to_the_country(): void
    {
        Http::fake(['us-api.opsyra.test/*' => Http::response(['status' => true])]);
        $company = $this->company(['trial_ends_at' => now()->addDays(2)]);

        $this->actingAs($this->admin)->post("/admin/companies/{$company->id}/extend-trial", ['days' => 14])->assertSessionHas('status');

        $this->assertSame(now()->addDays(16)->toDateString(), $company->fresh()->trial_ends_at->toDateString());
        $this->assertNotNull($company->fresh()->synced_at);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), "/api/internal/tenants/{$company->client_code}/subscription")
            && $r['status'] === 'trial'
            && hash_equals(hash_hmac('sha256', $r->header('X-Opsyra-Timestamp')[0].'.'.$r->body(), self::SECRET), $r->header('X-Opsyra-Signature')[0]));
    }

    public function test_past_due_is_sent_to_the_country_as_active(): void
    {
        Http::fake(['us-api.opsyra.test/*' => Http::response(['status' => true])]);
        $company = $this->company(['status' => 'active']);

        $this->actingAs($this->admin)->post("/admin/companies/{$company->id}/status", ['status' => 'past_due']);

        $this->assertSame('past_due', $company->fresh()->status);
        Http::assertSent(fn (Request $r) => $r['status'] === 'active');
    }

    public function test_a_failed_push_is_kept_and_flagged_not_lost(): void
    {
        Http::fake(['us-api.opsyra.test/*' => Http::response(['message' => 'down'], 503)]);
        $company = $this->company(['status' => 'active']);

        $this->actingAs($this->admin)->post("/admin/companies/{$company->id}/status", ['status' => 'suspended'])->assertSessionHas('warning');

        $company->refresh();
        $this->assertSame('suspended', $company->status, 'Central is the source of truth, even when the push fails.');
        $this->assertStringContainsString('503', $company->sync_error);
    }

    public function test_usage_sync_updates_staff_counts(): void
    {
        $company = $this->company(['client_code' => 'acme']);
        Http::fake(['us-api.opsyra.test/*' => Http::response(['status' => true, 'data' => [['client_code' => 'acme', 'active_employees' => 42]]])]);

        $this->artisan('companies:sync-usage')->assertSuccessful();

        $this->assertSame(42, $company->fresh()->active_employees);
    }

    public function test_companies_list_filters_and_searches(): void
    {
        $this->company(['name' => 'Alpha Trading', 'status' => 'active']);
        $this->company(['name' => 'Beta Foods', 'status' => 'trial']);

        $this->actingAs($this->admin)->get('/admin/companies?status=active')->assertSee('Alpha Trading')->assertDontSee('Beta Foods');
        $this->actingAs($this->admin)->get('/admin/companies?q=beta')->assertSee('Beta Foods')->assertDontSee('Alpha Trading');
    }

    public function test_the_admin_area_is_closed_to_visitors(): void
    {
        $company = $this->company();

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post("/admin/companies/{$company->id}/status", ['status' => 'cancelled'])->assertRedirect('/admin/login');
        $this->assertSame('trial', $company->fresh()->status);
    }
}
