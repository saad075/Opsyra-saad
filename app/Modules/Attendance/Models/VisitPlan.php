<?php

namespace App\Modules\Attendance\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** A repeating visit: who goes where, on which weekdays, from when to when. */
class VisitPlan extends Model
{
    use BelongsToTenant;

    protected $fillable = ['visit_location_id', 'user_id', 'weekdays', 'start_time', 'end_time', 'starts_on', 'ends_on', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['weekdays' => 'array', 'starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function location()
    {
        return $this->belongsTo(VisitLocation::class, 'visit_location_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function runsOn(string $date, int $isoWeekday): bool
    {
        return $this->starts_on->toDateString() <= $date
            && (! $this->ends_on || $this->ends_on->toDateString() >= $date)
            && in_array($isoWeekday, array_map('intval', $this->weekdays), true);
    }
}
