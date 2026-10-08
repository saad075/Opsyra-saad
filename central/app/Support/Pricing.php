<?php

namespace App\Support;

use App\Models\PlanPrice;

/**
 * monthly = base_price + max(0, employees − included_employees) × extra_employee_price,
 * rounded to the currency's minor units (OMR has three decimal places).
 * The same rounding rule as the country apps' Payroll Money helper.
 */
final class Pricing
{
    private const THREE_DECIMALS = ['OMR', 'BHD', 'KWD'];

    public static function monthly(PlanPrice $price, int $employees): float
    {
        $extra = max(0, $employees - $price->included_employees);

        return self::round($price->base_price + $extra * $price->extra_employee_price, $price->currency);
    }

    public static function decimals(string $currency): int
    {
        return in_array(strtoupper($currency), self::THREE_DECIMALS, true) ? 3 : 2;
    }

    public static function round(float $amount, string $currency): float
    {
        return round($amount, self::decimals($currency));
    }

    public static function format(float $amount, string $currency): string
    {
        return $currency.' '.number_format($amount, self::decimals($currency));
    }
}
