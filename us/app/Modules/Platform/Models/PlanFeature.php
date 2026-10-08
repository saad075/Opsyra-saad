<?php

namespace App\Modules\Platform\Models;

use Illuminate\Database\Eloquent\Model;

class PlanFeature extends Model
{
    protected $fillable = [
        'plan_id',
        'feature_key',
        'value',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
