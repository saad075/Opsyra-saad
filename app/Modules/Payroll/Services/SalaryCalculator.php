<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Payroll\Support\Money;

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
 */
class SalaryCalculator
{
    /**
     * @return array{currency: string, lines: list<array>, gross: float, deductions: float, net: float}
     */
    public function calculate(StaffSalary $salary, float $factor = 1.0): array
    {
        $currency = $salary->currency;
        $items = $salary->components()->with('component')->get()
            ->sortBy(fn ($item) => [$item->component->sequence, $item->component->id]);

        $basicItem = $items->first(fn ($item) => $item->component->is_basic);
        $basic = $basicItem ? $this->fixed($basicItem, $factor, $currency) : 0.0;

        $lines = [];
        $gross = 0.0;

        foreach ($items->filter(fn ($item) => $item->component->type === 'earning') as $item) {
            $amount = $item->component->is_basic
                ? $basic
                : $this->amount($item, $factor, $basic, 0.0, $currency);

            $lines[] = $this->line($item, $amount);
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

    private function line($item, float $amount): array
    {
        return [
            'source' => 'component',
            'pay_component_id' => $item->component->id,
            'code' => $item->component->code,
            'label' => $item->component->name,
            'type' => $item->component->type,
            'amount' => $amount,
        ];
    }
}
