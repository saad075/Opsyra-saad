<?php

use App\Modules\Tenancy\Support\TenantRls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Replaces SLMS360's staff_holidays, staff_attendance_dates,
// staff_attendances and the claim columns on attendance dates.
//
// attendance_days records what happened, not what it costs: SLMS360's 14
// codes (P, LT, LTD, EO, EOD, SL, EOA, A, L, UL, H, RW, UD, MC) folded pay
// decisions into the record at check-in time. Here a day has one status
// plus late / left-early / missed-checkout facts and minutes; the company's
// attendance policy turns those into pay at pay-run time
// (docs/research/attendance.md).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete(); // null = every branch
            $table->date('date');
            $table->string('name');
            $table->timestamps();

            $table->index(['tenant_id', 'date']);
        });

        TenantRls::enable('holidays');

        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date'); // the person's local working day (a night shift belongs to the day it starts)
            // present, half_day, absent, on_leave, remote, day_off, holiday, open (still clocked in)
            $table->string('status');
            $table->boolean('leave_paid')->nullable(); // for on_leave: paid or unpaid
            $table->string('expected')->nullable(); // work, day_off, holiday, visits — what the calendar said
            $table->unsignedSmallInteger('scheduled_minutes')->default(0);
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->boolean('is_late')->default(false);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->boolean('left_early')->default(false);
            $table->unsignedSmallInteger('early_minutes')->default(0);
            $table->boolean('missed_checkout')->default(false);
            $table->timestampTz('first_in_at')->nullable();
            $table->timestampTz('last_out_at')->nullable();
            $table->foreignId('work_schedule_id')->nullable()->constrained('work_schedules')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->boolean('is_manual')->default(false); // status set by HR; clocking no longer changes it
            $table->string('source')->default('system'); // app, manual, system, correction
            $table->text('note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('closed_at')->nullable(); // settled by the close-out; later edits re-evaluate it as a finished day
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'date']);
            $table->index(['tenant_id', 'date', 'status']);
        });

        TenantRls::enable('attendance_days');

        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendance_day_id')->constrained('attendance_days')->cascadeOnDelete();
            $table->string('type'); // in, out
            $table->timestampTz('punched_at');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('accuracy_m')->nullable(); // the phone's own estimate
            $table->unsignedInteger('distance_m')->nullable(); // from the branch (or visit location)
            $table->boolean('within_range')->nullable(); // null = no location check applied
            $table->string('source')->default('app'); // app, web, manual, correction
            $table->string('device')->nullable();
            $table->timestampTz('voided_at')->nullable(); // replaced by an approved correction; kept for the record
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'punched_at']);
        });

        TenantRls::enable('attendance_punches');

        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->time('requested_in')->nullable();
            $table->time('requested_out')->nullable();
            $table->string('requested_status')->nullable(); // remote, on_leave — "I wasn't absent, I was…"
            $table->text('reason');
            $table->string('status')->default('open'); // open, approved, rejected
            $table->text('response')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        DB::statement("CREATE UNIQUE INDEX attendance_corrections_one_open ON attendance_corrections (tenant_id, user_id, date) WHERE status = 'open'");

        TenantRls::enable('attendance_corrections');
    }

    public function down(): void
    {
        foreach (['attendance_corrections', 'attendance_punches', 'attendance_days', 'holidays'] as $name) {
            TenantRls::disable($name);
            Schema::dropIfExists($name);
        }
    }
};
