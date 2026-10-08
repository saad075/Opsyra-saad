<?php

namespace App\Modules\Rbac\Policies;

use App\Models\User;
use App\Modules\Rbac\Models\Role;

/**
 * Every action checks permission AND ownership (here: tenant), not just
 * authentication — the issue class the v2.5.0 SLMS360 security review found
 * (see docs/inventory.md). The ownership check below is belt-and-suspenders:
 * RLS and BelongsToTenant already make a cross-tenant $role impossible to
 * load in the first place, but a Policy is exactly where this project wants
 * that check to be visible and explicit, not just implied by a scope
 * somewhere else.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('HRMS-RoleManagement');
    }

    public function create(User $user): bool
    {
        return $user->can('HRMS-RoleManagement');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('HRMS-RoleManagement') && $role->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('HRMS-RoleManagement') && $role->tenant_id === $user->tenant_id;
    }
}
