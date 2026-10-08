<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contact fields are inline (contact_name/email/phone) rather than a
 * foreign key to a separate Contacts/Person module — Krayin's leads.person_id
 * points at its Contact package, which this phase deliberately doesn't
 * port (user decision, 2026-10-02 — scoped to the Leads module only). See
 * docs/research/crm-leads.md.
 *
 * `status` follows Krayin's own convention exactly: nullable boolean, not a
 * tri-state enum — null = open, true = won, false = lost. `lost_reason` and
 * `closed_at` only mean something once status is no longer null.
 *
 * Unlike Krayin (lead_pipeline_id/lead_pipeline_stage_id/lead_source_id/
 * lead_type_id/user_id all either cascade or restrict on delete), every FK
 * here is nullOnDelete — matches Opsyra's own house style elsewhere
 * (users.hierarchy_parent_id, companies.owner_id in the earlier CRM
 * attempt): losing a pipeline, stage, source, type or owner should detach a
 * lead, never destroy or block deleting the thing it pointed to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('lead_value', 12, 2)->nullable();
            $table->boolean('status')->nullable();
            $table->text('lost_reason')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->date('expected_close_date')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->foreignId('lead_type_id')->nullable()->constrained('lead_types')->nullOnDelete();
            $table->foreignId('lead_pipeline_id')->nullable()->constrained('lead_pipelines')->nullOnDelete();
            $table->foreignId('lead_pipeline_stage_id')->nullable()->constrained('lead_pipeline_stages')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'lead_pipeline_id', 'lead_pipeline_stage_id']);
            $table->index(['tenant_id', 'status']);
        });

        TenantRls::enable('leads');
    }

    public function down(): void
    {
        TenantRls::disable('leads');
        Schema::dropIfExists('leads');
    }
};
