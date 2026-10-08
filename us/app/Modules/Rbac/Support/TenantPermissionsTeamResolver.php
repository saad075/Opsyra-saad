<?php

namespace App\Modules\Rbac\Support;

use App\Modules\Tenancy\Support\TenantContext;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * Wires spatie/laravel-permission's "team" concept to our tenant, so there is
 * exactly one source of truth for "which tenant is this request/job in" —
 * TenantContext (see App\Modules\Tenancy\Support\TenantContext::set/runAs).
 * A role's scope always matches whatever RLS is enforcing, automatically.
 *
 * setPermissionsTeamId() is a deliberate no-op: calling it directly would let
 * spatie's notion of "current team" drift out of sync with TenantContext /
 * app.tenant_id. Go through Tenant::set() or Tenant::runAs() instead.
 */
class TenantPermissionsTeamResolver implements PermissionsTeamResolver
{
    public function setPermissionsTeamId($id): void
    {
        //
    }

    public function getPermissionsTeamId(): int|string|null
    {
        $tenant = app(TenantContext::class);

        return $tenant->check() ? $tenant->id() : null;
    }
}
