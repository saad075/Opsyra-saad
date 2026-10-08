<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// New — one checklist per staff member per onboarding/offboarding, with its
// tasks copied from the template at creation time, so later template edits
// never rewrite a checklist that's already under way.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // onboarding, offboarding
            $table->foreignId('checklist_template_id')->nullable()->constrained('checklist_templates')->nullOnDelete();
            $table->foreignId('staff_onboarding_request_id')->nullable()->constrained('staff_onboarding_requests')->nullOnDelete();
            $table->foreignId('staff_offboard_request_id')->nullable()->constrained('staff_offboard_requests')->nullOnDelete();
            $table->date('anchor_date'); // joining date or last working day
            $table->string('status')->default('in_progress'); // in_progress, completed, cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id']);
        });

        TenantRls::enable('staff_checklists');

        Schema::create('staff_checklist_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('staff_checklist_id')->constrained('staff_checklists')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sequence')->default(0);
            $table->string('status')->default('todo'); // todo, in_progress, done, skipped
            $table->text('note')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'staff_checklist_id']);
            $table->index(['tenant_id', 'assignee_user_id', 'status']);
            $table->index(['tenant_id', 'assignee_role_id', 'status']);
        });

        TenantRls::enable('staff_checklist_tasks');
    }

    public function down(): void
    {
        TenantRls::disable('staff_checklist_tasks');
        Schema::dropIfExists('staff_checklist_tasks');
        TenantRls::disable('staff_checklists');
        Schema::dropIfExists('staff_checklists');
    }
};
