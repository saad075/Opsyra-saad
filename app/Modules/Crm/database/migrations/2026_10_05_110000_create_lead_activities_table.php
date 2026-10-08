<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The "Activity" quick action — a scheduled call/meeting/lunch/deadline
 * logged against a lead, the way Krayin's own Activity type field works.
 * Consolidated to one table with a `type` column rather than Krayin's
 * fuller Activity package (participants, file attachments on the activity
 * itself, calendar integration) — this phase's version is a log entry you
 * can mark done, not a scheduling system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('comment')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->boolean('is_done')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'lead_id']);
        });

        TenantRls::enable('lead_activities');
    }

    public function down(): void
    {
        TenantRls::disable('lead_activities');
        Schema::dropIfExists('lead_activities');
    }
};
