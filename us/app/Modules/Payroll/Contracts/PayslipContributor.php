<?php

namespace App\Modules\Payroll\Contracts;

use App\Models\User;
use App\Modules\Payroll\Models\StaffSalary;
use Carbon\CarbonInterface;

/**
 * How another module adds lines to a payslip without Payroll knowing about
 * it. The Attendance Hub will implement this for absence/late/unpaid-leave
 * deductions (SLMS360's AttendanceDeductionCalculator), and register it
 * with $app->tag($class, PayslipContributor::TAG). Until then nothing is
 * tagged and payslips carry no attendance lines — the decision recorded in
 * docs/research/salaries.md.
 */
interface PayslipContributor
{
    public const TAG = 'payroll.payslip_contributors';

    /**
     * $calculated is the salary's own component lines and totals for this
     * period (SalaryCalculator output), for contributors that need e.g. a
     * per-day rate.
     *
     * @return list<array{source: string, code?: ?string, label: string, type: 'earning'|'deduction', amount: float}>
     */
    public function linesFor(User $staff, StaffSalary $salary, CarbonInterface $periodStart, CarbonInterface $periodEnd, array $calculated): array;
}
