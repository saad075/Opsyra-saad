<?php

namespace App\Modules\Payroll\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SalaryClaim extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'payslip_id',
        'user_id',
        'reason',
        'status',
        'response',
        'salary_adjustment_id',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adjustment()
    {
        return $this->belongsTo(SalaryAdjustment::class, 'salary_adjustment_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
