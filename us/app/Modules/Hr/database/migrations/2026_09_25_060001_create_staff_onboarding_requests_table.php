<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adapted from SLMS360's 2026_09_11_000006_create_staff_onboarding_requests_table.php
// — client_id -> tenant_id (real FK + RLS), user FKs made real constraints.
// Dropped backfill_attendance: it only drove the attendance backfill on
// approval, which belongs to the Attendance Hub (not built yet). Also
// dropped intended_status: SLMS360 froze active-vs-pending at submission
// time, so a request approved after the join date had passed still left the
// person 'pending' until the nightly job — it's now derived from joined_at
// at approval time instead.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_onboarding_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete(); // HR user who confirmed a rejected candidate was notified
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        TenantRls::enable('staff_onboarding_requests');
    }

    public function down(): void
    {
        TenantRls::disable('staff_onboarding_requests');
        Schema::dropIfExists('staff_onboarding_requests');
    }
};
