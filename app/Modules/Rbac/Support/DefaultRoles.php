<?php

namespace App\Modules\Rbac\Support;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Rbac\Models\Role;
use App\Modules\Tenancy\Support\TenantContext;

/**
 * Roles every new tenant starts with, so the first admin has something to
 * hand out on day one — the Setup wizard's "Approvers" step becomes "put
 * someone in the Senior approver role". Tenants can rename, edit or delete
 * them (Administrator excepted in spirit: it's what their first admin holds).
 */
final class DefaultRoles
{
    public const ADMINISTRATOR = 'Administrator';

    public static function definitions(): array
    {
        return [
            self::ADMINISTRATOR => array_keys(PermissionCatalogue::PERMISSIONS),
            'HR Manager' => ['HRMS-StaffManagement', 'HRMS-StaffPermissionUpdate'],
            'Payroll Officer' => ['HRMS-SalaryManagement', 'HRMS-StaffAdjustments'],
            'Approver' => ['HRMS-Approval-L1'],
            'Senior approver' => ['HRMS-Approval-L2'],
        ];
    }

    /**
     * Idempotent. Runs under the tenant's context: roles are tenant rows
     * (RLS), and spatie's team id comes from that same context.
     */
    public static function seedFor(Tenant $tenant): void
    {
        PermissionCatalogue::sync();

        app(TenantContext::class)->runAs($tenant->id, function () {
            foreach (self::definitions() as $name => $permissions) {
                Role::findOrCreate($name, PermissionCatalogue::GUARD)->syncPermissions($permissions);
            }
        });
    }
}
