<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['code', 'name', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function features()
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function prices()
    {
        return $this->hasMany(PlanPrice::class);
    }

    public function priceFor(string $country): ?PlanPrice
    {
        return $this->prices->firstWhere('country', $country);
    }

    /** @return list<string> */
    public function featureCodes(): array
    {
        return $this->features->pluck('feature_code')->all();
    }
}
