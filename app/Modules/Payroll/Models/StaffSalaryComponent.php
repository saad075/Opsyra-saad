<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StaffSalaryComponent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'staff_salary_id',
        'pay_component_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
        ];
    }

    public function salary()
    {
        return $this->belongsTo(StaffSalary::class, 'staff_salary_id');
    }

    public function component()
    {
        return $this->belongsTo(PayComponent::class, 'pay_component_id');
    }
}
