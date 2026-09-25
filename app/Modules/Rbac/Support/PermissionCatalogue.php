<?php

namespace App\Modules\Rbac\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Every permission Opsyra checks, in one place, with a plain description
 * for the role-editing screen. Permissions are the app's fixed global
 * capability list (not tenant data — docs/decisions.md), so they're synced
 * on deploy (migration + `permissions:sync`), not created ad hoc. Names are
 * SLMS360's where it had one.
 *
 * Adding a permission check anywhere means adding it here.
 */
final class PermissionCatalogue
{
    public const GUARD = 'sanctum';

    public const PERMISSIONS = [
        'HRMS-CompanySettings' => ['Setup', 'Run the setup wizard and edit company details'],
        'HRMS-RoleManagement' => ['Access', 'Create and edit roles'],
        'HRMS-StaffPermissionUpdate' => ['Access', 'Change the roles and permissions of staff'],
        'HRMS-StaffManagement' => ['HR', 'Add and edit staff, branches, departments, leaving reasons and checklists'],
        'HRMS-Approval-L1' => ['Approvals', 'Approve salaries at level 1'],
        'HRMS-Approval-L2' => ['Approvals', 'Final approval of new hires, leavers and salaries'],
        'HRMS-SalaryManagement' => ['Payroll', 'Set salaries and bank details, run payroll'],
        'HRMS-StaffAdjustments' => ['Payroll', 'Add one-off salary adjustments'],
    ];

    public static function sync(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<array{name: string, group: string, description: string}>
     */
    public static function describe(): array
    {
        return collect(self::PERMISSIONS)
            ->map(fn (array $meta, string $name) => ['name' => $name, 'group' => $meta[0], 'description' => $meta[1]])
            ->values()
            ->all();
    }
}
