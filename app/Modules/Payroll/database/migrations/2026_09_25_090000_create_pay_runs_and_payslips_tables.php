<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adapted from SLMS360's staff_salary_posts — one row per person per month
// with ~40 denormalised columns (a column per allowance, per deduction type,
// per attendance count). Here a pay run groups the payslips for one period,
// and a payslip is a header plus itemised lines (component, adjustment, and
// later attendance), so a new pay component never needs a migration. The
// period is start/end dates rather than month/year so biweekly fits.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pay_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('frequency'); // monthly, biweekly
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date')->nullable();
            $table->string('status')->default('processing'); // processing, completed
            $table->unsignedInteger('payslips_count')->default(0);
            $table->json('errors')->nullable(); // [{user_id, message}] — per-person failures
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'frequency', 'period_start']);
        });

        TenantRls::enable('pay_runs');

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('pay_run_id')->constrained('pay_runs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('staff_salary_id')->nullable()->constrained('staff_salaries')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->char('currency', 3);
            $table->unsignedSmallInteger('period_days');
            $table->unsignedSmallInteger('payment_days'); // days employed within the period
            $table->decimal('gross_earnings', 15, 3);
            $table->decimal('total_deductions', 15, 3);
            $table->decimal('net_pay', 15, 3);
            $table->string('status')->default('draft'); // draft, l1_approved, approved, carried_forward
            $table->string('payment_method')->nullable(); // transfer, cheque — set on final approval
            $table->string('cheque_number')->nullable();
            $table->foreignId('l1_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('l1_approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'period_start', 'period_end']);
            $table->index(['tenant_id', 'pay_run_id', 'status']);
        });

        TenantRls::enable('payslips');

        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();
            $table->string('source'); // component, adjustment, attendance, …
            $table->foreignId('pay_component_id')->nullable()->constrained('pay_components')->nullOnDelete();
            $table->foreignId('salary_adjustment_id')->nullable()->constrained('salary_adjustments')->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('label');
            $table->string('type'); // earning, deduction
            $table->decimal('amount', 15, 3);
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'payslip_id']);
        });

        TenantRls::enable('payslip_lines');

        Schema::table('salary_adjustments', function (Blueprint $table) {
            $table->foreignId('payslip_id')->nullable()->constrained('payslips')->nullOnDelete();
        });

        Schema::create('salary_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->string('status')->default('open'); // open, approved, rejected
            $table->text('response')->nullable();
            $table->foreignId('salary_adjustment_id')->nullable()->constrained('salary_adjustments')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        TenantRls::enable('salary_claims');
    }

    public function down(): void
    {
        TenantRls::disable('salary_claims');
        Schema::dropIfExists('salary_claims');

        Schema::table('salary_adjustments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payslip_id');
        });

        TenantRls::disable('payslip_lines');
        Schema::dropIfExists('payslip_lines');
        TenantRls::disable('payslips');
        Schema::dropIfExists('payslips');
        TenantRls::disable('pay_runs');
        Schema::dropIfExists('pay_runs');
    }
};
