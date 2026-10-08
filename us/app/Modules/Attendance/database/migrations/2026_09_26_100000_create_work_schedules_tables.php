<?php

use App\Modules\Attendance\Models\WorkSchedule;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Replaces SLMS360's staff_attendance_shifts (+ shift_times, one row per
// weekday) and users.attendance_shift_id. Two changes, both from Frappe
// HRMS's Shift Type / Shift Assignment (docs/research/attendance.md):
// - the schedule holds only *when* people work; pay consequences (lates
//   allowed, deduction %) moved to the company's attendance policy, applied
//   at pay-run time;
// - who works which schedule is dated (work_schedule_assignments), so a
//   change of schedule keeps history instead of overwriting one column.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('grace_minutes')->default(10); // late only after start + grace
            $table->unsignedSmallInteger('early_leave_grace_minutes')->default(10); // left early only before end − grace
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        TenantRls::enable('work_schedules');

        Schema::create('work_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('work_schedule_id')->constrained('work_schedules')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // ISO: 1 = Monday … 7 = Sunday
            $table->boolean('is_working')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable(); // before start_time = ends the next day (night shift)
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'work_schedule_id', 'weekday']);
        });

        TenantRls::enable('work_schedule_days');

        Schema::create('work_schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('work_schedule_id')->constrained('work_schedules')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'starts_on']);
        });

        TenantRls::enable('work_schedule_assignments');

        // How a person's attendance is tracked: against a weekly schedule,
        // by visits to client locations (home care), or not at all.
        Schema::table('users', function (Blueprint $table) {
            $table->string('attendance_mode')->default('schedule'); // schedule, visits, not_tracked
        });

        // Office geofence: clock-ins far from the branch are recorded but
        // flagged for review (user decision 2026-09-26: never blocked).
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('geofence_radius_m')->nullable(); // null = no location check
        });

        Tenant::withTrashed()->each(fn (Tenant $tenant) => WorkSchedule::seedDefaultsFor($tenant));
    }

    public function down(): void
    {
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn(['latitude', 'longitude', 'geofence_radius_m']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('attendance_mode'));

        foreach (['work_schedule_assignments', 'work_schedule_days', 'work_schedules'] as $name) {
            TenantRls::disable($name);
            Schema::dropIfExists($name);
        }
    }
};
