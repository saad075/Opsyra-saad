<?php

namespace App\Modules\Setup\Steps;

use App\Models\User;
use App\Modules\Rbac\Models\Role;
use App\Modules\Setup\Contracts\SetupStepProvider;
use App\Modules\Setup\Models\TenantSetting;
use App\Modules\Setup\Support\SetupStep;
use Spatie\Permission\Models\Permission;

class CompanySetupSteps implements SetupStepProvider
{
    public function steps(): array
    {
        return [
            new SetupStep(
                'company_details', 'company', 10,
                'Company details',
                'Your company name, address, timezone and currency.',
                check: fn () => ['done' => filled(TenantSetting::get('company_name'))
                    && filled(TenantSetting::get('address'))
                    && filled(TenantSetting::get('timezone'))],
            ),
            new SetupStep(
                'roles', 'people', 10,
                'Roles',
                'Job roles that group permissions, e.g. HR Manager, Payroll Officer.',
                check: function () {
                    $count = Role::count();

                    return ['done' => $count > 0, 'detail' => $count ? "{$count} role(s)" : null];
                },
            ),
            new SetupStep(
                'approvers', 'people', 20,
                'Approvers',
                'Who approves new hires, leavers and salaries. At least one person needs level 2 approval.',
                check: fn () => [
                    'done' => $this->holders('HRMS-Approval-L2') > 0,
                    'detail' => $this->holders('HRMS-Approval-L2').' level 2, '.$this->holders('HRMS-Approval-L1').' level 1 approver(s)',
                ],
            ),
        ];
    }

    /**
     * People holding a permission directly or through a role. Spatie's
     * permission() scope throws if the permission row doesn't exist yet.
     */
    private function holders(string $permission): int
    {
        if (! Permission::where('name', $permission)->where('guard_name', 'sanctum')->exists()) {
            return 0;
        }

        return User::permission($permission)->count();
    }
}
