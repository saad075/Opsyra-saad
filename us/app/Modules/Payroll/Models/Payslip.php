<?php

namespace App\Modules\Payroll\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'pay_run_id',
        'user_id',
        'staff_salary_id',
        'period_start',
        'period_end',
        'currency',
        'period_days',
        'payment_days',
        'gross_earnings',
        'total_deductions',
        'net_pay',
        'status',
        'payment_method',
        'cheque_number',
        'l1_approved_by',
        'l1_approved_at',
        'approved_by',
        'approved_at',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'gross_earnings' => 'float',
            'total_deductions' => 'float',
            'net_pay' => 'float',
            'l1_approved_at' => 'datetime',
            'approved_at' => 'datetime',
            'generated_at' => 'datetime',
        ];
    }

    public function payRun()
    {
        return $this->belongsTo(PayRun::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lines()
    {
        return $this->hasMany(PayslipLine::class)->orderBy('sequence')->orderBy('id');
    }

    public function claims()
    {
        return $this->hasMany(SalaryClaim::class);
    }

    public function l1ApprovedBy()
    {
        return $this->belongsTo(User::class, 'l1_approved_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
