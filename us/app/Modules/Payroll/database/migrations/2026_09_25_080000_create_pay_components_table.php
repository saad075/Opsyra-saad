<?php

use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Replaces SLMS360's fixed user_salaries columns (monthly_salary, medical,
// house_rent, ta, mobile, tax, tax_amount) with a per-tenant list of pay
// components — the model Frappe HRMS ("Salary Component") and Horilla
// ("Allowance"/"Deduction") share. See docs/research/salaries.md.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pay_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code'); // stable key, e.g. BASIC, HOUSE_RENT
            $table->string('type'); // earning, deduction
            $table->string('calculation'); // fixed, percent_of_basic, percent_of_gross
            $table->boolean('is_basic')->default(false); // the component percent_of_basic refers to
            $table->boolean('prorate')->default(true); // scale fixed amounts for part-period employment
            $table->unsignedInteger('sequence')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['tenant_id', 'name']);
        });

        DB::statement('CREATE UNIQUE INDEX pay_components_one_basic ON pay_components (tenant_id) WHERE is_basic');

        TenantRls::enable('pay_components');

        Tenant::withTrashed()->each(fn (Tenant $tenant) => PayComponent::seedDefaultsFor($tenant));
    }

    public function down(): void
    {
        TenantRls::disable('pay_components');
        Schema::dropIfExists('pay_components');
    }
};
