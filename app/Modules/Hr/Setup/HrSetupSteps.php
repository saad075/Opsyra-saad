<?php

namespace App\Modules\Hr\Setup;

use App\Models\User;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\Department;
use App\Modules\Hr\Models\Designation;
use App\Modules\Setup\Contracts\SetupStepProvider;
use App\Modules\Setup\Support\SetupStep;

/**
 * The HR module's part of the Setup wizard. Departure reasons and checklist
 * templates are pre-filled per country, so they're review steps.
 */
class HrSetupSteps implements SetupStepProvider
{
    public function steps(): array
    {
        return [
            new SetupStep(
                'branches', 'company', 20,
                'Branches',
                'Your offices or sites. Staff are assigned to at least one.',
                check: fn () => $this->counted(Branch::count(), 'branch(es)'),
            ),
            new SetupStep(
                'departments', 'company', 30,
                'Departments',
                'How your organisation is divided, e.g. Finance, Operations.',
                check: fn () => $this->counted(Department::count(), 'department(s)'),
            ),
            new SetupStep(
                'designations', 'company', 40,
                'Job titles',
                'Designations within each department.',
                required: false,
                check: fn () => $this->counted(Designation::count(), 'job title(s)'),
            ),
            new SetupStep(
                'staff', 'people', 30,
                'Staff',
                'Add your employees, one by one for now.',
                // The admin doing setup is already a user.
                check: fn () => ['done' => User::count() > 1, 'detail' => (User::count() - 1).' staff member(s) besides you'],
            ),
            SetupStep::review(
                'departure_reasons', 'hr', 10,
                'Leaving reasons',
                'Reasons recorded when someone leaves. Pre-filled for your country — rename, add or deactivate as needed.',
            ),
            SetupStep::review(
                'checklist_templates', 'hr', 20,
                'Onboarding and offboarding checklists',
                'Task lists created when someone joins or leaves. Starter templates are included — adapt them to your company.',
            ),
        ];
    }

    private function counted(int $count, string $noun): array
    {
        return ['done' => $count > 0, 'detail' => $count ? "{$count} {$noun}" : null];
    }
}
