<?php

namespace App\Modules\Rbac\Models;

use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Swapped in for spatie/laravel-permission's own Role model via
 * config/permission.php so roles get the same app-layer BelongsToTenant scope
 * every other tenant-scoped model gets — RLS (enabled in this module's
 * migration) already enforces the boundary at the DB layer regardless, but
 * this keeps the two-layer pattern consistent and explicit for a model we
 * didn't otherwise write ourselves.
 *
 * Permission is NOT subclassed the same way: permissions are the app's fixed,
 * global capability list, not tenant data — see docs/decisions.md.
 */
class Role extends SpatieRole
{
    use BelongsToTenant;
}
