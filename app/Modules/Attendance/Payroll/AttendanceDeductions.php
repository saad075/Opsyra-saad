<?php

namespace App\Modules\Attendance\Payroll;

use App\Models\User;
use App\Modules\Attendance\Models\AttendanceDay;
use App\Modules\Attendance\Support\AttendancePolicy;
use App\Modules\Payroll\Contracts\PayslipContributor;
use App\Modules\Payroll\Models\StaffSalary;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Attendance's effect on a salaried payslip, from the company's rules
 * (SLMS360's AttendanceDeductionCalculator, simplified — see
 * docs/decisions.md). Off until the company turns deductions on.
 *
 * A day's pay is the period's fixed earnings ÷ the days in the period (as
 * SLMS360). Each kind of deduction is its own payslip line, so the person
 * can see exactly why. "First N a month free" counts each calendar month
 * from its first day, so a two-weekly period spanning two months is fair.
 * Hourly-only pay has nothing to deduct: unworked hours are simply unpaid.
 */
class AttendanceDeductions implements PayslipContributor
{
    public function linesFor(User $staff, StaffSalary $salary, CarbonInterface $periodStart, CarbonInterface $periodEnd, array $calculated): array
    {
        $policy = AttendancePolicy::all();

        if (! $policy['deductions_enabled']) {
            return [];
        }

        $fixedEarnings = collect($calculated['lines'])
            ->where('type', 'earning')
            ->reject(fn ($line) => $line['hourly'] ?? false)
            ->sum('amount');

        $periodDays = (int) $periodStart->diffInDays($periodEnd) + 1;
        $dayRate = $periodDays > 0 ? $fixedEarnings / $periodDays : 0.0;

        if ($dayRate <= 0) {
            return [];
        }

        $days = AttendanceDay::where('user_id', $staff->id)
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        $missedAs = $policy['missed_checkout_counts_as'];
        $missed = $days->where('status', 'present')->where('missed_checkout', true)->count();
        $absent = $days->where('status', 'absent')->count();
        $half = $days->where('status', 'half_day')->count();
        $unpaid = $days->where('status', 'on_leave')->where('leave_paid', false)->count();

        $lines = [];
        $add = function (string $code, string $label, float $days) use (&$lines, $dayRate) {
            if ($days > 0) {
                $lines[] = ['source' => 'attendance', 'code' => $code, 'label' => $label, 'type' => 'deduction', 'amount' => $days * $dayRate];
            }
        };

        $add('ATT_ABSENT', $this->plural($absent, 'absent day'), $absent * $policy['absent_deduction_days']);
        $add('ATT_HALF_DAY', $this->plural($half, 'half day'), $half * $policy['half_day_deduction_days']);
        $add('ATT_UNPAID_LEAVE', $this->plural($unpaid, 'day').' of unpaid leave', $unpaid * $policy['unpaid_leave_deduction_days']);

        if ($missedAs !== 'present') {
            $add('ATT_MISSED_CHECKOUT', $this->plural($missed, 'missed check-out'), $missed * ($missedAs === 'absent' ? $policy['absent_deduction_days'] : $policy['half_day_deduction_days']));
        }

        $lates = $this->beyondFree($staff, $days, 'is_late', $policy['free_lates_per_month']);
        $add('ATT_LATE', $this->plural($lates, 'late arrival').' beyond '.$policy['free_lates_per_month'].' free a month', $lates * $policy['late_deduction_percent'] / 100);

        $early = $this->beyondFree($staff, $days, 'left_early', $policy['free_early_leaves_per_month']);
        $add('ATT_EARLY', $this->plural($early, 'early leave').' beyond '.$policy['free_early_leaves_per_month'].' free a month', $early * $policy['early_leave_deduction_percent'] / 100);

        return $lines;
    }

    /**
     * How many of the period's flagged days come after the month's free
     * allowance — counting that month's earlier days too, even before the
     * period started.
     */
    private function beyondFree(User $staff, $days, string $flag, int $free): int
    {
        $charged = 0;

        foreach ($days->where($flag, true)->groupBy(fn ($d) => $d->date->format('Y-m')) as $month => $inPeriod) {
            $monthStart = CarbonImmutable::parse("{$month}-01")->toDateString();
            $firstInPeriod = $inPeriod->min(fn ($d) => $d->date->toDateString());

            $earlier = AttendanceDay::where('user_id', $staff->id)
                ->where($flag, true)
                ->where('date', '>=', $monthStart)
                ->where('date', '<', $firstInPeriod)
                ->count();

            $charged += max(0, $earlier + $inPeriod->count() - max($free, $earlier));
        }

        return $charged;
    }

    private function plural(int $n, string $noun): string
    {
        return $n.' '.$noun.($n === 1 ? '' : 's');
    }
}
