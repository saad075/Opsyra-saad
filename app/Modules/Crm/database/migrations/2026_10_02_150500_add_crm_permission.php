<?php

use App\Modules\Rbac\Support\DefaultRoles;
use Illuminate\Database\Migrations\Migration;

// New permission for the Leads module: every existing tenant's
// Administrator role gets it (that role means "everything"); other roles
// are the tenant's to change on the Roles screen. New tenants get the
// default in DefaultRoles.
return new class extends Migration
{
    public function up(): void
    {
        DefaultRoles::grantToAdministrators('CRM-ManageLeads');
    }

    public function down(): void
    {
        // Permission may be in use by then; leave it.
    }
};
