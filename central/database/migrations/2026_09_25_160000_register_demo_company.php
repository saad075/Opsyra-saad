<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;

// The public demo company (built nightly on the country deployment by
// demo:reset) needs a register entry so "Log in" with code "demo" works.
// Flagged is_demo so the subscription dashboard leaves it out of revenue.
return new class extends Migration
{
    public function up(): void
    {
        Company::updateOrCreate(['client_code' => 'demo'], [
            'name' => 'Demo Company',
            'country' => config('opsyra.demo.country', 'us'),
            'status' => 'active',
            'admin_name' => 'Alex Morgan',
            'admin_email' => config('opsyra.demo.email'),
            'is_demo' => true,
        ]);
    }

    public function down(): void
    {
        Company::where('client_code', 'demo')->where('is_demo', true)->delete();
    }
};
