<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// New — no SLMS360 equivalent. Reusable onboarding/offboarding task lists,
// designed from the Frappe HRMS / Horilla / Odoo comparison in
// docs/research/onboarding-offboarding.md (design only, no code taken).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('type'); // onboarding, offboarding
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'type', 'name']);
        });

        // At most one default template per type per tenant.
        DB::statement('CREATE UNIQUE INDEX checklist_templates_one_default ON checklist_templates (tenant_id, type) WHERE is_default');

        TenantRls::enable('checklist_templates');

        Schema::create('checklist_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('checklist_template_id')->constrained('checklist_templates')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // Who does it, resolved when a checklist is created (Odoo's idea):
            // manager = the staff member's hierarchy parent, employee = the
            // staff member themselves, role = anyone holding the role, user =
            // one named person.
            $table->string('assignee_type');
            $table->foreignId('assignee_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Days relative to the joining date / last working day; negative
            // means before it (e.g. "prepare laptop" 3 days before start).
            $table->integer('due_offset_days')->default(0);
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'checklist_template_id']);
        });

        TenantRls::enable('checklist_template_tasks');
    }

    public function down(): void
    {
        TenantRls::disable('checklist_template_tasks');
        Schema::dropIfExists('checklist_template_tasks');
        TenantRls::disable('checklist_templates');
        Schema::dropIfExists('checklist_templates');
    }
};
