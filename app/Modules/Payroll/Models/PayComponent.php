<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class PayComponent extends Model
{
    use BelongsToTenant;

    public const TYPES = ['earning', 'deduction'];

    // per_hour: the value is a rate, paid for the period's verified hours (earnings only).
    public const CALCULATIONS = ['fixed', 'percent_of_basic', 'percent_of_gross', 'per_hour'];

    protected $fillable = [
        'name',
        'code',
        'type',
        'calculation',
        'is_basic',
        'prorate',
        'sequence',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_basic' => 'boolean',
            'prorate' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    /**
     * Starting lists per country deployment, for each tenant to edit — not
     * statutory tax or social-security rules. Rates and amounts are set per
     * salary, never here. Pakistan mirrors SLMS360's fixed columns so
     * existing SLMS360 users see the same pay items.
     */
    public static function defaultsFor(string $country): array
    {
        $basic = ['name' => 'Basic', 'code' => 'BASIC', 'type' => 'earning', 'calculation' => 'fixed', 'is_basic' => true, 'sequence' => 1];

        return array_merge([$basic], match (strtolower($country)) {
            'pk' => [
                ['name' => 'House rent', 'code' => 'HOUSE_RENT', 'type' => 'earning', 'calculation' => 'fixed', 'sequence' => 10],
                ['name' => 'Medical', 'code' => 'MEDICAL', 'type' => 'earning', 'calculation' => 'fixed', 'sequence' => 20],
                ['name' => 'Travel', 'code' => 'TRAVEL', 'type' => 'earning', 'calculation' => 'fixed', 'sequence' => 30],
                ['name' => 'Mobile', 'code' => 'MOBILE', 'type' => 'earning', 'calculation' => 'fixed', 'sequence' => 40],
                ['name' => 'Income tax (%)', 'code' => 'TAX_PERCENT', 'type' => 'deduction', 'calculation' => 'percent_of_gross', 'sequence' => 80],
                ['name' => 'Income tax (fixed)', 'code' => 'TAX_FIXED', 'type' => 'deduction', 'calculation' => 'fixed', 'sequence' => 81],
            ],
            'om' => [
                ['name' => 'Housing allowance', 'code' => 'HOUSING', 'type' => 'earning', 'calculation' => 'fixed', 'sequence' => 10],
                ['name' => 'Transport allowance', 'code' => 'TRANSPORT', 'type' => 'earning', 'calculation' => 'fixed', 'sequence' => 20],
                ['name' => 'Social insurance', 'code' => 'SOCIAL_INSURANCE', 'type' => 'deduction', 'calculation' => 'percent_of_basic', 'sequence' => 80],
            ],
            'gb', 'uk' => [
                ['name' => 'Car allowance', 'code' => 'CAR_ALLOWANCE', 'type' => 'earning', 'calculation' => 'fixed', 'sequence' => 10],
                ['name' => 'Pension', 'code' => 'PENSION', 'type' => 'deduction', 'calculation' => 'percent_of_basic', 'sequence' => 80],
            ],
            // US: federal/state withholding comes from the US payroll module.
            default => [],
        });
    }

    public static function seedDefaultsFor(Tenant $tenant): void
    {
        app(TenantContext::class)->runAs($tenant->id, function () use ($tenant) {
            foreach (static::defaultsFor($tenant->country) as $component) {
                static::firstOrCreate(['code' => $component['code']], $component + ['is_system' => true]);
            }
        });
    }

    public static function basic(): ?self
    {
        return static::where('is_basic', true)->first();
    }
}
