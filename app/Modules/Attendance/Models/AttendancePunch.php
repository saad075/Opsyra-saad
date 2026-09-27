<?php

namespace App\Modules\Attendance\Models;

use App\Casts\UtcDateTime;
use App\Modules\Hr\Models\Branch;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** One clock-in or clock-out, with where it happened. */
class AttendancePunch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'attendance_day_id', 'type', 'punched_at', 'branch_id', 'latitude', 'longitude',
        'accuracy_m', 'distance_m', 'within_range', 'source', 'device', 'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'punched_at' => UtcDateTime::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_m' => 'integer',
            'distance_m' => 'integer',
            'within_range' => 'boolean',
            'voided_at' => UtcDateTime::class,
        ];
    }

    public function day()
    {
        return $this->belongsTo(AttendanceDay::class, 'attendance_day_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
