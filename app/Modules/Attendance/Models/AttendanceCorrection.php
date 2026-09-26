<?php

namespace App\Modules\Attendance\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * A staff member asking for a day to be put right — forgot to clock out,
 * was working remotely, was on leave (SLMS360's "attendance claim").
 */
class AttendanceCorrection extends Model
{
    use BelongsToTenant;

    public const REQUESTABLE_STATUSES = ['remote', 'on_leave'];

    protected $fillable = ['user_id', 'date', 'requested_in', 'requested_out', 'requested_status', 'reason', 'status', 'response', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['date' => 'date', 'resolved_at' => 'datetime'];
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
