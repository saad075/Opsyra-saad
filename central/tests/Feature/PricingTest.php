<?php

namespace Tests\Feature;

use App\Models\CountryPricing;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use App\Support\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['is_active' => true]);
    }

    protected function plan(string $code): Plan
    {
        return Plan::where('code', $code)->firstOrFail();
    }

    public function test_the_monthly_price_follows_the_users_worked_example(): void
    {
        // Plus in the US: $30 for up to 10 employees, $1.50 per extra.
        $plus = $this->plan('plus')->priceFor('us');

        $this->assertEquals(30.00, $plus->monthlyFor(1), 'Fewer than the included employees still pays the starting price.');
        $this->assertEquals(30.00, $plus->monthlyFor(10));
        $this->assertEquals(52.50, $plus->monthlyFor(25)); // 30 + 15 × 1.50
        $this->assertEquals(25.00, $this->plan('base')->priceFor('us')->monthlyFor(20)); // 15 + 10 × 1
        $this->assertEquals(80.00, $this->plan('premium')->priceFor('us')->monthlyFor(25)); // 50 + 15 × 2
    }

    public function test_omani_rial_prices_keep_three_decimals(): void
    {
        $price = new PlanPrice(['currency' => 'OMR', 'base_price' => 5.75, 'included_employees' => 10, 'extra_employee_price' => 0.385]);

        $this->assertEquals(7.675, Pricing::monthly($price, 15)); // 5.75 + 5 × 0.385
        $this->assertSame('OMR 7.675', Pricing::format(7.675, 'OMR'));
    }

    public function test_every_country_starts_unpublished_so_the_site_says_coming_soon(): void
    {
        foreach (array_keys(config('opsyra.countries')) as $country) {
            $this->assertFalse(CountryPricing::isPublished($country));
        }

        $this->get('/pricing?country=us')->assertOk()->assertSee('Plans coming soon')->assertDontSee('52.50');
    }

    public function test_the_admin_area_needs_a_signed_in_active_admin(): void
    {
        $this->get('/admin/pricing')->assertRedirect('/admin/login');

        $inactive = $this->admin(['is_active' => false, 'password' => 'secret-pass-123']);
        $this->post('/admin/login', ['email' => $inactive->email, 'password' => 'secret-pass-123'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $active = $this->admin(['password' => 'secret-pass-123']);
        $this->post('/admin/login', ['email' => $active->email, 'password' => 'secret-pass-123'])->assertRedirect('/admin/pricing'); // back to the page they first asked for
        $this->get('/admin/pricing/us')->assertOk()->assertSee('Save prices');
    }

    public function test_an_admin_sets_all_nine_numbers_for_a_country(): void
    {
        $admin = $this->admin();
        $plans = Plan::orderBy('sort_order')->get();

        $this->actingAs($admin)->post('/admin/pricing/gb', ['prices' => [
            $plans[0]->id => ['base_price' => 12, 'included_employees' => 10, 'extra_employee_price' => 0.8],
            $plans[1]->id => ['base_price' => 24, 'included_employees' => 10, 'extra_employee_price' => 1.2],
            $plans[2]->id => ['base_price' => 40, 'included_employees' => 15, 'extra_employee_price' => 1.6],
        ]])->assertRedirect('/admin/pricing/gb');

        $premium = $plans[2]->fresh('prices')->priceFor('gb');
        $this->assertSame('GBP', $premium->currency);
        $this->assertEquals(56.00, $premium->monthlyFor(25)); // 40 + 10 × 1.6
    }

    public function test_a_half_filled_plan_is_rejected(): void
    {
        $plan = $this->plan('base');

        $this->actingAs($this->admin())
            ->post('/admin/pricing/om', ['prices' => [$plan->id => ['base_price' => 5, 'included_employees' => '', 'extra_employee_price' => '']]])
            ->assertSessionHasErrors("prices.{$plan->id}");
    }

    public function test_a_country_can_only_be_published_once_every_plan_has_a_price(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/pricing/pk/publish', ['publish' => '1'])->assertSessionHasErrors('publish');
        $this->assertFalse(CountryPricing::isPublished('pk'));

        $this->actingAs($admin)->post('/admin/pricing/us/publish', ['publish' => '1'])->assertRedirect('/admin/pricing/us');
        $this->assertTrue(CountryPricing::isPublished('us'));

        $this->get('/pricing?country=us&employees=25')
            ->assertOk()
            ->assertDontSee('Plans coming soon')
            ->assertSee('USD 52.50');
    }

    public function test_an_admin_edits_a_plans_name_and_features(): void
    {
        $plan = $this->plan('base');

        $this->actingAs($this->admin())->post("/admin/plans/{$plan->id}", [
            'name' => 'Starter',
            'description' => 'For small teams.',
            'sort_order' => 1,
            'is_active' => '1',
            'features' => ['people', 'checklists'],
        ])->assertRedirect('/admin/plans');

        $plan->refresh();
        $this->assertSame('Starter', $plan->name);
        $this->assertEqualsCanonicalizing(['people', 'checklists'], $plan->featureCodes());
    }

    public function test_a_plan_cannot_be_given_an_unknown_feature(): void
    {
        $this->actingAs($this->admin())->post('/admin/plans/'.$this->plan('base')->id, [
            'name' => 'Base', 'sort_order' => 1, 'features' => ['free-money'],
        ])->assertSessionHasErrors('features.0');
    }

    public function test_the_landing_page_renders_with_its_calls_to_action(): void
    {
        $this->get('/')->assertOk()->assertSee('One connected')->assertSee('Log in')->assertSee('Get started')->assertSee('Explore the live demo');
    }
}
