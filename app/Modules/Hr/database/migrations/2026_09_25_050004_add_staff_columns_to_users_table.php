<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidates ~15 separate SLMS360 "alter users, add one column" migrations
 * (dob, shift_id, hierarchy_parent_id, is_remote, lefted_at, blood_group,
 * birthday check, device_token/device_id, justify_work_hours, left_reason,
 * team_name, designation, etc — accumulated 2024–2026) into one migration,
 * scoped to exactly what this module's ported staff/team endpoints use.
 *
 * Deliberately NOT ported from that column history: SLMS360's `role` string
 * (Client/Staff/Parent/Super Admin) — Opsyra already has a real `tenants`
 * platform table and spatie roles (see the Rbac module), so there's no
 * "which account type is this user" discriminator to carry over; every row
 * in a tenant's users table already means "this tenant's staff". Also
 * dropped: country_id (no Country model ported), attendance_shift_id /
 * annual_leaves_allowed (Attendance Hub's concern, not built yet — add
 * there when it exists), username, colour/social/stripe/subscription/
 * payroll-classification columns (unrelated concerns, other future modules).
 * `cnic` (Pakistan national ID) is generalized to `national_id` — this
 * product targets four countries, not just Pakistan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active')->after('tenant_id');
            $table->string('designation')->nullable();
            $table->string('team_name')->nullable();
            $table->foreignId('hierarchy_parent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('dob')->nullable();
            $table->string('phone')->nullable();
            $table->string('national_id')->nullable();
            $table->date('joined_at')->nullable();
            $table->boolean('is_remote')->default(false);
            $table->string('blood_group')->nullable();
            $table->string('avatar')->nullable();
            $table->string('device_id')->nullable();
            $table->timestamp('lefted_at')->nullable();
            $table->string('left_reason')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->boolean('celebrate_birthday')->default(true);
            $table->boolean('justify_work_hours')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hierarchy_parent_id');
            $table->dropColumn([
                'status', 'designation', 'team_name', 'dob', 'phone',
                'national_id', 'joined_at', 'is_remote', 'blood_group',
                'avatar', 'device_id', 'lefted_at', 'left_reason',
                'address', 'city', 'zip_code', 'celebrate_birthday',
                'justify_work_hours',
            ]);
        });
    }
};
