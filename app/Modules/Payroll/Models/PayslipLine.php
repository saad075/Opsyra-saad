<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PayslipLine extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'payslip_id',
        'source',
        'pay_component_id',
        'salary_adjustment_id',
        'code',
        'label',
        'type',
        'amount',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }
}
