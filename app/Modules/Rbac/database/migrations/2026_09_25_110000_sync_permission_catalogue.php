<?php

use App\Modules\Platform\Models\Tenant;
use App\Modules\Rbac\Support\DefaultRoles;
use App\Modules\Rbac\Support\PermissionCatalogue;
use Illuminate\Database\Migrations\Migration;

// Until now permissions only existed where a test created them, so a real
// deployment would have had none. Creates the catalogue, and gives
// tenants that already exist the same default roles a new tenant gets.
return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalogue::sync();

        Tenant::withTrashed()->each(fn (Tenant $tenant) => DefaultRoles::seedFor($tenant));
    }

    public function down(): void
    {
        // Permissions and roles may be in use by then; leave them.
    }
};
