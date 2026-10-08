<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The one piece of Krayin's Activity/changelog feed ported in this phase —
 * not a general activity log (no Notes/Calls/Meetings/Files/Emails; that
 * whole package stays out of scope, see docs/research/crm-leads.md). One
 * immutable row per actual stage transition, written by
 * Lead::moveToStage() only when the stage genuinely changes — the initial
 * stage a lead is created on isn't a "change" (nothing to change from), so
 * it isn't logged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_stage_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            // Both nullOnDelete, not cascade — same reasoning as every other
            // FK in this module (see create_leads_table): a deleted stage
            // should blank out of old history, not take the history itself
            // with it. "to" is otherwise NOT NULL at the application layer
            // (every change has a real destination); the column is nullable
            // purely so a later stage deletion doesn't orphan the row.
            $table->foreignId('from_lead_pipeline_stage_id')->nullable()->constrained('lead_pipeline_stages')->nullOnDelete();
            $table->foreignId('to_lead_pipeline_stage_id')->nullable()->constrained('lead_pipeline_stages')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'lead_id']);
        });

        TenantRls::enable('lead_stage_changes');
    }

    public function down(): void
    {
        TenantRls::disable('lead_stage_changes');
        Schema::dropIfExists('lead_stage_changes');
    }
};
