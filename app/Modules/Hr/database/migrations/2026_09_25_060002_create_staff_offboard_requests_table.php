<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adapted from SLMS360's 2026_09_11_000005_create_staff_offboard_requests_table.php
// — client_id -> tenant_id (real FK + RLS), user FKs made real constraints.
// The free-text `reason` is replaced by departure_reason_id + an optional
// note; cancelled_by/cancelled_at added because SLMS360's cancelOffboarding
// cleared the user's fields but left the request row saying 'approved'.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_offboard_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('last_working_day');
            $table->foreignId('departure_reason_id')->constrained('departure_reasons')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('new_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('direct_reports_count')->default(0);
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        TenantRls::enable('staff_offboard_requests');
    }

    public function down(): void
    {
        TenantRls::disable('staff_offboard_requests');
        Schema::dropIfExists('staff_offboard_requests');
    }
};
