<?php

namespace App\Modules\Hr\Models;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }

    protected $fillable = [
        'name',
        'status',
        'address',
        'city',
        'phone',
        'timezone',
        'latitude',
        'longitude',
        'geofence_radius_m',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'geofence_radius_m' => 'integer',
        ];
    }

    public function userBranches()
    {
        return $this->hasMany(UserBranch::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
