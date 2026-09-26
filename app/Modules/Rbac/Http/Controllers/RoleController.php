<?php

namespace App\Modules\Rbac\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Rbac\Models\Role;
use App\Modules\Rbac\Support\PermissionCatalogue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Adapted from SLMS360's app/Http/Controllers/Client/RoleController.php
 * (see docs/provenance.md for the source commit). Changes from the original:
 *   - Authorization moved from inline Auth::user()->can(...) checks to
 *     RolePolicy (registered in RbacServiceProvider) — same permission,
 *     'HRMS-RoleManagement', but now also checks tenant ownership on
 *     update/delete, not just the permission (see RolePolicy's docblock).
 *   - $request->user() instead of the Auth facade, consistent with the rest
 *     of Opsyra (see ResolveTenantFromUser).
 *   - name uniqueness and role-id existence are validated *within the
 *     tenant* explicitly (Rule::unique()/exists()->where('tenant_id', ...))
 *     rather than globally — SLMS360 didn't need this (single tenant per
 *     deployment); RLS also enforces it as a backstop, but the validation
 *     rule needs to match the tenant_id-leading composite unique index.
 *   - guard_name is 'sanctum' (this app's actual auth guard), not SLMS360's
 *     'api' (Passport).
 *   - permissions are synced by name (self-documenting wire format), and
 *     validated against the global permissions table — permissions aren't
 *     tenant data, so no tenant check applies to them (see docs/decisions.md).
 */
class RoleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::where('guard_name', 'sanctum')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->each(function (Role $role) {
                $role->staff_count = User::role($role)->count();
            });

        return response()->json([
            'status' => true,
            'message' => 'Roles retrieved successfully',
            'data' => $roles,
        ]);
    }

    /** Every permission a role can hold, grouped and described in plain words. */
    public function permissions()
    {
        $this->authorize('viewAny', Role::class);

        return response()->json(['status' => true, 'message' => 'Permissions', 'data' => PermissionCatalogue::describe()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Role::class);

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('roles')->where('tenant_id', $tenantId),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'sanctum',
        ]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return response()->json([
            'status' => true,
            'message' => 'Role created successfully.',
        ], 201);
    }

    public function update(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => [
                'required',
                Rule::exists('roles', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => [
                'required',
                'string',
                Rule::unique('roles')->where('tenant_id', $tenantId)->ignore($request->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::findById($validated['id'], 'sanctum');

        $this->authorize('update', $role);

        $role->name = $validated['name'];
        $role->save();
        $role->syncPermissions($validated['permissions'] ?? []);

        return response()->json([
            'status' => true,
            'message' => 'Role updated successfully.',
        ]);
    }

    public function destroy(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => [
                'required',
                Rule::exists('roles', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        $role = Role::findById($validated['id'], 'sanctum');

        $this->authorize('delete', $role);

        if (User::role($role)->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'This role is still assigned to staff. Reassign them before deleting it.',
            ], 409);
        }

        $role->delete();

        return response()->json([
            'status' => true,
            'message' => 'Role deleted successfully.',
        ]);
    }
}
