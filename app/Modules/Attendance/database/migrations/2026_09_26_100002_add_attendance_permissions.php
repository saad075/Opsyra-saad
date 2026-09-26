<?php

use App\Modules\Rbac\Support\DefaultRoles;
use Illuminate\Database\Migrations\Migration;

// New permissions for Attendance: every existing tenant's Administrator
// role gets them (that role means "everything"); other roles are the
// tenant's to change on the Roles screen. New tenants get the defaults in
// DefaultRoles.
return new class extends Migration
{
    public function up(): void
    {
        DefaultRoles::grantToAdministrators('HRMS-AttendanceManagement');
        DefaultRoles::grantToAdministrators('HRMS-VisitPlanning');
    }

    public function down(): void
    {
        // Permissions may be in use by then; leave them.
    }
};
