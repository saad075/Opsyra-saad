<?php

namespace App\Modules\Payroll\Setup;

use App\Models\User;
use App\Modules\Payroll\Models\StaffBankAccount;
use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Setup\Contracts\SetupStepProvider;
use App\Modules\Setup\Support\SetupStep;

/**
 * The Payroll module's part of the Setup wizard. Pay components are
 * pre-filled per country, so they're a review step; salaries are done once
 * every active employee has one; bank details are optional (without them
 * a payslip is paid by cheque).
 */
class PayrollSetupSteps implements SetupStepProvider
{
    public function steps(): array
    {
        return [
            SetupStep::review(
                'pay_components', 'payroll', 10,
                'Pay components',
                'The earnings and deductions that make up a salary. Pre-filled for your country — add allowances or deductions your company uses.',
            ),
            new SetupStep(
                'salaries', 'payroll', 20,
                'Salaries',
                'Set each employee\'s salary and pay frequency.',
                check: function () {
                    [$total, $with] = $this->coverage(fn (User $u) => StaffSalary::effectiveFor($u->id, today()) !== null);

                    return ['done' => $total > 0 && $with === $total, 'detail' => "{$with} of {$total} active staff have a salary"];
                },
            ),
            new SetupStep(
                'bank_accounts', 'payroll', 30,
                'Bank details',
                'Where to pay each employee. Anyone without bank details is paid by cheque.',
                required: false,
                check: function () {
                    $accounts = StaffBankAccount::pluck('user_id')->flip();
                    [$total, $with] = $this->coverage(fn (User $u) => $accounts->has($u->id));

                    return ['done' => $total > 0 && $with === $total, 'detail' => "{$with} of {$total} active staff have bank details"];
                },
            ),
        ];
    }

    /**
     * @return array{0: int, 1: int} [active staff, how many pass $test]
     */
    private function coverage(\Closure $test): array
    {
        $active = User::where('status', 'active')->get(['id']);

        return [$active->count(), $active->filter($test)->count()];
    }
}
