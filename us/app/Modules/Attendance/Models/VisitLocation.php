<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Hr\Models\Branch;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** A client's home (or other place) that staff visit. */
class VisitLocation extends Model
{
    use BelongsToTenant;

    protected $fillable = ['branch_id', 'name', 'reference', 'address', 'city', 'postcode', 'latitude', 'longitude', 'geofence_radius_m', 'access_notes', 'is_active'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'geofence_radius_m' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function hasPoint(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
