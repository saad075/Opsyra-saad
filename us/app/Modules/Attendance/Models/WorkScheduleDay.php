<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WorkScheduleDay extends Model
{
    use BelongsToTenant;

    public const NAMES = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    protected $fillable = ['work_schedule_id', 'weekday', 'is_working', 'start_time', 'end_time', 'break_minutes'];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'is_working' => 'boolean',
            'break_minutes' => 'integer',
        ];
    }

    /** Ends after midnight (e.g. 22:00–06:00). */
    public function isOvernight(): bool
    {
        return $this->is_working && $this->end_time !== null && $this->end_time <= $this->start_time;
    }

    /** Minutes expected at work: the span less the break. */
    public function requiredMinutes(): int
    {
        if (! $this->is_working || ! $this->start_time || ! $this->end_time) {
            return 0;
        }

        [$sh, $sm] = array_map('intval', explode(':', $this->start_time));
        [$eh, $em] = array_map('intval', explode(':', $this->end_time));
        $span = ($eh * 60 + $em) - ($sh * 60 + $sm);

        if ($span <= 0) {
            $span += 24 * 60;
        }

        return max(0, $span - $this->break_minutes);
    }
}
