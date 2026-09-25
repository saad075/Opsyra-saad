<?php

use App\Modules\Hr\Models\ChecklistTemplate;
use App\Modules\Platform\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lets HR pick which checklist template an onboarding/offboarding uses when
// submitting it; null means "the tenant's default for that type". Also seeds
// the starter templates for tenants that already exist (new tenants get them
// from HrServiceProvider).
return new class extends Migration
{
    public function up(): void
    {
        foreach (['staff_onboarding_requests', 'staff_offboard_requests'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('checklist_template_id')->nullable()->constrained('checklist_templates')->nullOnDelete();
            });
        }

        Tenant::withTrashed()->each(fn (Tenant $tenant) => ChecklistTemplate::seedDefaultsFor($tenant));
    }

    public function down(): void
    {
        foreach (['staff_onboarding_requests', 'staff_offboard_requests'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('checklist_template_id');
            });
        }
    }
};
