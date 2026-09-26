<?php

namespace App\Modules\Attendance\Models;

use App\Models\User;
use App\Modules\Hr\Models\Branch;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * One person's working day: what happened (status, lateness, minutes), not
 * what it costs — the attendance policy decides that at pay-run time.
 */
class AttendanceDay extends Model
{
    use BelongsToTenant;

    public const STATUSES = ['present', 'half_day', 'absent', 'on_leave', 'remote', 'day_off', 'holiday', 'open'];

    /** Statuses HR may set by hand. */
    public const MANUAL_STATUSES = ['present', 'half_day', 'absent', 'on_leave', 'remote', 'day_off', 'holiday'];

    protected $fillable = [
        'user_id', 'date', 'status', 'leave_paid', 'expected', 'scheduled_minutes', 'worked_minutes',
        'is_late', 'late_minutes', 'left_early', 'early_minutes', 'missed_checkout',
        'first_in_at', 'last_out_at', 'work_schedule_id', 'branch_id', 'is_manual', 'source', 'note', 'updated_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'leave_paid' => 'boolean',
            'scheduled_minutes' => 'integer',
            'worked_minutes' => 'integer',
            'is_late' => 'boolean',
            'late_minutes' => 'integer',
            'left_early' => 'boolean',
            'early_minutes' => 'integer',
            'missed_checkout' => 'boolean',
            'first_in_at' => 'datetime',
            'last_out_at' => 'datetime',
            'is_manual' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function punches()
    {
        return $this->hasMany(AttendancePunch::class)->orderBy('punched_at');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function schedule()
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }
}
