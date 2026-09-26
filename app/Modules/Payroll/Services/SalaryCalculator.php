<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Payroll\Models\StaffSalaryComponent;
use App\Modules\Payroll\Support\Money;
use Illuminate\Support\Collection;

/**
 * Turns a salary's components into itemised lines for one pay period.
 * Replaces SLMS360's hard-coded "ta + house_rent + mobile + monthly_salary
 * + medical" sum and its flat tax block (StaffSalaryCommand).
 *
 * Order matters and is fixed: basic → other earnings (fixed, or % of basic)
 * → gross → deductions (fixed, % of basic, or % of gross). $factor scales
 * fixed amounts on prorating components for part-period employment
 * (joined or left mid-period); percentage lines follow from the already
 * scaled basic/gross. Every line is rounded to the currency's minor units
 * before totalling, so the payslip adds up exactly as printed.
 *
 * An hourly component's value is a rate; it pays rate × $hours (the
 * period's verified hours, from WorkedHoursProvider). With no hours given —
 * a preview, or the salaries list — it shows as nil.
 */
class SalaryCalculator
{
    /**
     * @return array{currency: string, lines: list<array>, gross: float, deductions: float, net: float}
     */
    public function calculate(StaffSalary $salary, float $factor = 1.0, ?float $hours = null): array
    {
        return $this->calculateItems($salary->components()->with('component')->get(), $salary->currency, $factor, $hours);
    }

    /**
     * The same calculation on components that needn't be saved — the salary
     * editor's live preview uses it, so the preview can't drift from what a
     * payslip will actually say.
     *
     * @param  Collection<int, StaffSalaryComponent>  $items  each with its component relation set
     * @return array{currency: string, lines: list<array>, gross: float, deductions: float, net: float}
     */
    public function calculateItems(Collection $items, string $currency, float $factor = 1.0, ?float $hours = null): array
    {
        $items = $items->sortBy(fn ($item) => [$item->component->sequence, $item->component->id]);

        $basicItem = $items->first(fn ($item) => $item->component->is_basic);
        $basic = $basicItem ? $this->fixed($basicItem, $factor, $currency) : 0.0;

        $lines = [];
        $gross = 0.0;

        foreach ($items->filter(fn ($item) => $item->component->type === 'earning') as $item) {
            $amount = match (true) {
                $item->component->is_basic => $basic,
                $item->component->calculation === 'per_hour' => Money::round($item->value * ($hours ?? 0), $currency),
                default => $this->amount($item, $factor, $basic, 0.0, $currency),
            };

            $lines[] = $this->line($item, $amount, $item->component->calculation === 'per_hour' ? $hours : null);
            $gross += $amount;
        }

        $deductions = 0.0;

        foreach ($items->filter(fn ($item) => $item->component->type === 'deduction') as $item) {
            $amount = $this->amount($item, $factor, $basic, $gross, $currency);

            $lines[] = $this->line($item, $amount);
            $deductions += $amount;
        }

        return [
            'currency' => $currency,
            'lines' => $lines,
            'gross' => Money::round($gross, $currency),
            'deductions' => Money::round($deductions, $currency),
            'net' => Money::round($gross - $deductions, $currency),
        ];
    }

    private function amount($item, float $factor, float $basic, float $gross, string $currency): float
    {
        return match ($item->component->calculation) {
            'percent_of_basic' => Money::round($basic * $item->value / 100, $currency),
            'percent_of_gross' => Money::round($gross * $item->value / 100, $currency),
            default => $this->fixed($item, $factor, $currency),
        };
    }

    private function fixed($item, float $factor, string $currency): float
    {
        return Money::round($item->value * ($item->component->prorate ? $factor : 1.0), $currency);
    }

    private function line($item, float $amount, ?float $hours = null): array
    {
        $label = $item->component->name;

        if ($hours !== null) {
            $label .= ' ('.rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.').' h)';
        }

        return [
            'source' => 'component',
            'pay_component_id' => $item->component->id,
            'code' => $item->component->code,
            'label' => $label,
            'hourly' => $item->component->calculation === 'per_hour',
            'type' => $item->component->type,
            'amount' => $amount,
        ];
    }
}
