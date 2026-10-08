<?php

namespace App\Modules\Payroll\Services;

use App\Models\User;
use App\Modules\Hr\Models\StaffOnboardingRequest;
use App\Modules\Payroll\Contracts\PayslipContributor;
use App\Modules\Payroll\Contracts\WorkedHoursProvider;
use App\Modules\Payroll\Exceptions\PayrollException;
use App\Modules\Payroll\Models\PayRun;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Models\SalaryAdjustment;
use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Payroll\Support\Money;
use App\Modules\Payroll\Support\PayPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Adapted from SLMS360's StaffSalaryCommand / StaffSalaryBiWeeklyCommand /
 * RegenrateStaffSalaryCommand. Per person: salary in force at the end of
 * the period → component lines (prorated for days employed) → lines from
 * other modules (attendance, later) → every not-yet-applied adjustment
 * dated up to the period end. Adjustments are marked as consumed by this
 * payslip, so they're applied exactly once — SLMS360 re-summed a month's
 * adjustments on every (re)generation.
 *
 * As in SLMS360, percentage deductions (e.g. tax % of gross) are worked out
 * on the salary's own gross before adjustments are added.
 */
class PayslipGenerator
{
    public function __construct(private SalaryCalculator $calculator, private WorkedHoursProvider $hours) {}

    /**
     * Staff to include in a run: employed for at least one day of the
     * period and with a salary of the run's frequency. Excludes anyone whose
     * onboarding is still awaiting approval or was rejected.
     */
    public function eligibleStaff(PayRun $run)
    {
        $period = PayPeriod::for($run->frequency, $run->period_start);

        return User::where('status', '!=', 'pending_approval')
            ->where(fn ($q) => $q->whereNull('joined_at')->orWhereDate('joined_at', '<=', $period->end))
            ->where(fn ($q) => $q->whereNull('lefted_at')->orWhereDate('lefted_at', '>=', $period->start))
            ->get()
            ->filter(function (User $staff) use ($run, $period) {
                $latestOnboarding = StaffOnboardingRequest::where('user_id', $staff->id)->latest('id')->value('status');

                return ! in_array($latestOnboarding, ['pending', 'rejected'], true)
                    && $period->employedDays($staff->joined_at, $staff->lefted_at) > 0
                    && StaffSalary::effectiveFor($staff->id, $period->end)?->pay_frequency === $run->frequency;
            })
            ->values();
    }

    public function generate(PayRun $run, User $staff): Payslip
    {
        return DB::transaction(function () use ($run, $staff) {
            $existing = Payslip::where('user_id', $staff->id)
                ->whereDate('period_start', $run->period_start)
                ->whereDate('period_end', $run->period_end)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new PayrollException('A payslip already exists for this person and period.');
            }

            $payslip = new Payslip(['pay_run_id' => $run->id, 'user_id' => $staff->id]);
            $this->fill($payslip, $run, $staff);

            return $payslip;
        });
    }

    /**
     * Recalculates a draft payslip in place (e.g. after a salary correction).
     * Approved payslips are final — SLMS360 regenerated them too, silently
     * changing pay someone had already signed off.
     */
    public function regenerate(Payslip $payslip): Payslip
    {
        return DB::transaction(function () use ($payslip) {
            $payslip = Payslip::whereKey($payslip->id)->lockForUpdate()->firstOrFail();

            if ($payslip->status !== 'draft') {
                throw new PayrollException('Only a draft payslip can be regenerated.');
            }

            SalaryAdjustment::where('payslip_id', $payslip->id)->update(['payslip_id' => null]);
            $payslip->lines()->delete();

            $this->fill($payslip, $payslip->payRun, $payslip->staff);

            return $payslip;
        });
    }

    private function fill(Payslip $payslip, PayRun $run, User $staff): void
    {
        $period = PayPeriod::for($run->frequency, $run->period_start);
        $salary = StaffSalary::effectiveFor($staff->id, $period->end);

        if (! $salary || $salary->pay_frequency !== $run->frequency) {
            throw new PayrollException("No {$run->frequency} salary is set up for this person.");
        }

        $paymentDays = $period->employedDays($staff->joined_at, $staff->lefted_at);

        if ($paymentDays === 0) {
            throw new PayrollException('This person was not employed during the period.');
        }

        // Hourly pay: the period's verified hours (only asked for when the salary pays by the hour).
        $hours = $salary->components()->whereHas('component', fn ($q) => $q->where('calculation', 'per_hour'))->exists()
            ? $this->hours->minutesFor($staff, $period->start, $period->end) / 60
            : null;

        $calculated = $this->calculator->calculate($salary, $paymentDays / $period->days(), $hours);
        $currency = $salary->currency;
        $lines = $calculated['lines'];

        foreach (app()->tagged(PayslipContributor::TAG) as $contributor) {
            foreach ($contributor->linesFor($staff, $salary, $period->start, $period->end, $calculated) as $line) {
                $lines[] = ['amount' => Money::round($line['amount'], $currency)] + $line;
            }
        }

        $adjustments = SalaryAdjustment::where('user_id', $staff->id)
            ->whereNull('payslip_id')
            ->whereDate('effective_date', '<=', $period->end)
            ->orderBy('effective_date')
            ->lockForUpdate()
            ->get();

        foreach ($adjustments as $adjustment) {
            $lines[] = [
                'source' => 'adjustment',
                'salary_adjustment_id' => $adjustment->id,
                'code' => $adjustment->label,
                'label' => $adjustment->title,
                'type' => $adjustment->type === 'credit' ? 'earning' : 'deduction',
                'amount' => Money::round($adjustment->amount, $currency),
            ];
        }

        $gross = collect($lines)->where('type', 'earning')->sum('amount');
        $deductions = collect($lines)->where('type', 'deduction')->sum('amount');

        $payslip->fill([
            'staff_salary_id' => $salary->id,
            'period_start' => $period->start,
            'period_end' => $period->end,
            'currency' => $currency,
            'period_days' => $period->days(),
            'payment_days' => $paymentDays,
            'gross_earnings' => Money::round($gross, $currency),
            'total_deductions' => Money::round($deductions, $currency),
            'net_pay' => Money::round($gross - $deductions, $currency),
            'status' => 'draft',
            'generated_at' => now(),
        ])->save();

        foreach (array_values($lines) as $i => $line) {
            $payslip->lines()->create([
                'source' => $line['source'],
                'pay_component_id' => $line['pay_component_id'] ?? null,
                'salary_adjustment_id' => $line['salary_adjustment_id'] ?? null,
                'code' => $line['code'] ?? null,
                'label' => $line['label'],
                'type' => $line['type'],
                'amount' => $line['amount'],
                'sequence' => $i + 1,
            ]);
        }

        SalaryAdjustment::whereIn('id', $adjustments->pluck('id'))->update(['payslip_id' => $payslip->id]);
    }
}
