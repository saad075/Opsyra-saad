<?php

namespace App\Modules\Attendance\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Who works which schedule, from when (Frappe's "Shift Assignment"). The
 * latest assignment starting on or before a date applies, unless it has
 * ended.
 */
class WorkScheduleAssignment extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'work_schedule_id', 'starts_on', 'ends_on', 'created_by'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function schedule()
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function forUserOn(int $userId, string $date): ?self
    {
        return static::with('schedule.days')
            ->where('user_id', $userId)
            ->whereDate('starts_on', '<=', $date)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->first();
    }
}
