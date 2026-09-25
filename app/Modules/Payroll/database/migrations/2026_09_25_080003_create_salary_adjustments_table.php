<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adapted from SLMS360's staff_adjustments. SLMS360 keyed an adjustment to a
// month/year and re-summed every adjustment for that month on each salary
// (re)generation; with biweekly periods that doesn't work, so here an
// adjustment has an effective date and is picked up by the pay run whose
// period contains it — and is marked with the payslip that consumed it
// (payslip_id, added with the payslips table), so it's applied exactly once.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // credit, debit
            $table->decimal('amount', 15, 3);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('label')->nullable(); // machine tag, e.g. salary_carry_forward
            $table->date('effective_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'effective_date']);
        });

        TenantRls::enable('salary_adjustments');
    }

    public function down(): void
    {
        TenantRls::disable('salary_adjustments');
        Schema::dropIfExists('salary_adjustments');
    }
};
