<?php

namespace App\Modules\Payroll\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PayRun extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'frequency',
        'period_start',
        'period_end',
        'pay_date',
        'status',
        'payslips_count',
        'errors',
        'created_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'pay_date' => 'date',
            'errors' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
