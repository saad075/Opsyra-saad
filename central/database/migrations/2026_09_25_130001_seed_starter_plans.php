<?php

use App\Models\CountryPricing;
use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

/**
 * Starting point only — names, features per tier and every price are
 * editable in the super-admin area. The US prices are the user's worked
 * example (2026-09-25), saved as an unpublished draft; the other countries
 * start empty. Nothing is shown publicly until a country is published.
 */
return new class extends Migration
{
    public function up(): void
    {
        $plans = [
            ['code' => 'base', 'name' => 'Base', 'sort_order' => 1, 'us' => [15, 10, 1],
                'description' => 'The essentials for running your people.',
                'features' => ['people', 'hiring_approvals', 'checklists', 'login_history']],
            ['code' => 'plus', 'name' => 'Plus', 'sort_order' => 2, 'us' => [30, 10, 1.5],
                'description' => 'Everything in Base, plus payroll.',
                'features' => ['people', 'hiring_approvals', 'checklists', 'login_history', 'payroll']],
            ['code' => 'premium', 'name' => 'Premium', 'sort_order' => 3, 'us' => [50, 10, 2],
                'description' => 'The full workspace, including everything we add next.',
                'features' => ['people', 'hiring_approvals', 'checklists', 'login_history', 'payroll', 'pay_approvals', 'attendance', 'dashboards', 'id_cards', 'bulk_import']],
        ];

        foreach ($plans as $data) {
            $plan = Plan::firstOrCreate(['code' => $data['code']], [
                'name' => $data['name'],
                'description' => $data['description'],
                'sort_order' => $data['sort_order'],
            ]);

            foreach ($data['features'] as $code) {
                $plan->features()->firstOrCreate(['feature_code' => $code]);
            }

            [$base, $included, $extra] = $data['us'];
            $plan->prices()->firstOrCreate(['country' => 'us'], [
                'currency' => 'USD',
                'base_price' => $base,
                'included_employees' => $included,
                'extra_employee_price' => $extra,
            ]);
        }

        foreach (array_keys(config('opsyra.countries')) as $country) {
            CountryPricing::firstOrCreate(['country' => $country], ['is_published' => false]);
        }
    }

    public function down(): void
    {
        Plan::whereIn('code', ['base', 'plus', 'premium'])->delete();
        CountryPricing::query()->delete();
    }
};
