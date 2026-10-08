<?php

namespace App\Modules\Platform\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'price_cents',
        'billing_period',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function features()
    {
        return $this->hasMany(PlanFeature::class);
    }
}
