<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adapted from SLMS360's user_salaries (one overwritable row per person,
// with user_salary_logs as the only history). Here a salary is
// effective-dated: a raise adds a new row from its start date and the old
// one stays, so "what was this person paid in March" is a query, not a
// log reconstruction. Amounts are per pay period of the salary's frequency.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('effective_from');
            $table->string('pay_frequency'); // monthly, biweekly
            $table->char('currency', 3);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'effective_from']);
        });

        TenantRls::enable('staff_salaries');

        Schema::create('staff_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('staff_salary_id')->constrained('staff_salaries')->cascadeOnDelete();
            $table->foreignId('pay_component_id')->constrained('pay_components')->restrictOnDelete();
            // An amount for fixed components, a percentage for percent_* ones.
            $table->decimal('value', 15, 3);
            $table->timestamps();

            $table->unique(['staff_salary_id', 'pay_component_id']);
        });

        TenantRls::enable('staff_salary_components');
    }

    public function down(): void
    {
        TenantRls::disable('staff_salary_components');
        Schema::dropIfExists('staff_salary_components');
        TenantRls::disable('staff_salaries');
        Schema::dropIfExists('staff_salaries');
    }
};
