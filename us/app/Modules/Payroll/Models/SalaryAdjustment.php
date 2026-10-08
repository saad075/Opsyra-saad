<?php

namespace App\Modules\Payroll\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SalaryAdjustment extends Model
{
    use BelongsToTenant;

    public const TYPES = ['credit', 'debit'];

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'title',
        'description',
        'label',
        'effective_date',
        'payslip_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'effective_date' => 'date',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
