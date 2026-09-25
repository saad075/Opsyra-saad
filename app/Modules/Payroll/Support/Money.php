<?php

namespace App\Modules\Payroll\Support;

/**
 * Money columns are decimal(15,3) — three places because Omani rial has
 * 1,000 baisa to the rial. Every computed amount is rounded to its own
 * currency's minor units here, so a PKR or GBP figure never carries a
 * stray third decimal. (SLMS360 used decimal(8,2): max 999,999.99, which a
 * PKR 1,000,000 salary overflows.)
 */
class Money
{
    private const DECIMALS = ['OMR' => 3, 'BHD' => 3, 'KWD' => 3];

    private const COUNTRY_CURRENCY = [
        'us' => 'USD',
        'gb' => 'GBP',
        'uk' => 'GBP',
        'om' => 'OMR',
        'pk' => 'PKR',
    ];

    public static function decimalsFor(string $currency): int
    {
        return self::DECIMALS[strtoupper($currency)] ?? 2;
    }

    public static function round(float $amount, string $currency): float
    {
        return round($amount, self::decimalsFor($currency));
    }

    public static function currencyForCountry(string $country): string
    {
        return self::COUNTRY_CURRENCY[strtolower($country)] ?? 'USD';
    }
}
