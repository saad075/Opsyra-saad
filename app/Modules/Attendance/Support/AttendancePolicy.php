<?php

namespace App\Modules\Attendance\Support;

use App\Modules\Setup\Models\TenantSetting;

/**
 * The company's attendance rules — how a day's facts are judged and what
 * they cost. SLMS360 kept these on each shift and baked them into the day
 * at check-in; here they're one company setting, read when a day is
 * evaluated (thresholds) or when a payslip is made (pay), so changing a
 * rule never needs past days rewritten (user decision 2026-09-26).
 */
final class AttendancePolicy
{
    public const KEY = 'attendance_policy';

    public const DEFAULTS = [
        // Judging a day (as a share of the scheduled minutes).
        'half_day_below_percent' => 50,   // worked less than this → half day
        'absent_below_percent' => 25,     // worked less than this → absent
        // Pay (applied by the payroll link; off until the company turns it on).
        'deductions_enabled' => false,
        'absent_deduction_days' => 1,     // days' pay per absent day
        'half_day_deduction_days' => 0.5,
        'unpaid_leave_deduction_days' => 1,
        'free_lates_per_month' => 3,      // lates after this many in a month cost…
        'late_deduction_percent' => 25,   // …this % of a day's pay each
        'free_early_leaves_per_month' => 3,
        'early_leave_deduction_percent' => 25,
        'missed_checkout_counts_as' => 'absent', // present, half_day, absent — SLMS360 deducted a full day until HR reviewed
    ];

    public const RULES = [
        'half_day_below_percent' => ['integer', 'between:0,100'],
        'absent_below_percent' => ['integer', 'between:0,100'],
        'deductions_enabled' => ['boolean'],
        'absent_deduction_days' => ['numeric', 'between:0,3'],
        'half_day_deduction_days' => ['numeric', 'between:0,1'],
        'unpaid_leave_deduction_days' => ['numeric', 'between:0,3'],
        'free_lates_per_month' => ['integer', 'between:0,31'],
        'late_deduction_percent' => ['numeric', 'between:0,100'],
        'free_early_leaves_per_month' => ['integer', 'between:0,31'],
        'early_leave_deduction_percent' => ['numeric', 'between:0,100'],
        'missed_checkout_counts_as' => ['string', 'in:present,half_day,absent'],
    ];

    /** @return array<string, mixed> */
    public static function all(): array
    {
        $stored = TenantSetting::get(self::KEY, []);

        return array_merge(self::DEFAULTS, array_intersect_key(is_array($stored) ? $stored : [], self::DEFAULTS));
    }

    public static function get(string $key): mixed
    {
        return self::all()[$key];
    }

    /** @param  array<string, mixed>  $values */
    public static function update(array $values): array
    {
        $merged = array_merge(self::all(), array_intersect_key($values, self::DEFAULTS));
        TenantSetting::set(self::KEY, $merged);

        return $merged;
    }
}
