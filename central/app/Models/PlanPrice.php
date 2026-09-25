<?php

namespace App\Models;

use App\Support\Pricing;
use Illuminate\Database\Eloquent\Model;

class PlanPrice extends Model
{
    protected $fillable = ['plan_id', 'country', 'currency', 'base_price', 'included_employees', 'extra_employee_price', 'updated_by'];

    protected function casts(): array
    {
        return [
            'base_price' => 'float',
            'included_employees' => 'integer',
            'extra_employee_price' => 'float',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function monthlyFor(int $employees): float
    {
        return Pricing::monthly($this, $employees);
    }
}
