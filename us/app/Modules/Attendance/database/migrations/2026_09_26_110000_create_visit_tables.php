<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Visit-based work (home care — Kindstead's case, user decision 2026-09-26):
// staff with no fixed workplace visit clients at home, 3–4 a day, changing
// daily. Each visit is checked in and out in the app with GPS, compared
// with the client's address; far away is flagged for a coordinator, never
// refused. New build — neither SLMS360 nor the open-source references have
// it (closest: Frappe's Employee Checkin geolocation, Horilla's geofencing).
//
// Kept deliberately minimal about the people being visited: a name, an
// address and a point on the map. No health or care information.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete(); // the branch that serves them
            $table->string('name'); // the client, e.g. "Mrs A. Patel"
            $table->string('reference')->nullable(); // the company's own client code
            $table->string('address');
            $table->string('city')->nullable();
            $table->string('postcode')->nullable();
            $table->decimal('latitude', 10, 7)->nullable(); // null: visits there can't be location-checked
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('geofence_radius_m')->default(150);
            $table->text('access_notes')->nullable(); // e.g. "ring twice, side door"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        TenantRls::enable('visit_locations');

        // A repeating visit: who goes where, on which weekdays, when.
        Schema::create('visit_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('visit_location_id')->constrained('visit_locations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('weekdays'); // ISO 1 = Monday … 7 = Sunday
            $table->time('start_time');
            $table->time('end_time');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        TenantRls::enable('visit_plans');

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('visit_location_id')->constrained('visit_locations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('visit_plan_id')->nullable()->constrained('visit_plans')->nullOnDelete();
            $table->date('date'); // the caregiver's local working day
            $table->timestampTz('planned_start');
            $table->timestampTz('planned_end');
            // planned, in_progress, completed, missed, cancelled
            $table->string('status')->default('planned');
            $table->timestampTz('checked_in_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->unsignedInteger('check_in_accuracy_m')->nullable();
            $table->unsignedInteger('check_in_distance_m')->nullable();
            $table->boolean('check_in_within_range')->nullable(); // null: the location has no map point
            $table->timestampTz('checked_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->unsignedInteger('check_out_accuracy_m')->nullable();
            $table->unsignedInteger('check_out_distance_m')->nullable();
            $table->boolean('check_out_within_range')->nullable();
            $table->boolean('missed_checkout')->default(false);
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            // needs_review (flagged), accepted, rejected — null when nothing to review
            $table->string('review_status')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('caregiver_note')->nullable(); // left at check-out
            $table->string('cancel_reason')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'date', 'status']);
            $table->index(['tenant_id', 'user_id', 'date']);
            $table->index(['tenant_id', 'review_status']);
        });

        // A plan makes at most one visit per day.
        DB::statement('CREATE UNIQUE INDEX visits_one_per_plan_day ON visits (tenant_id, visit_plan_id, date) WHERE visit_plan_id IS NOT NULL');

        TenantRls::enable('visits');
    }

    public function down(): void
    {
        foreach (['visits', 'visit_plans', 'visit_locations'] as $name) {
            TenantRls::disable($name);
            Schema::dropIfExists($name);
        }
    }
};
