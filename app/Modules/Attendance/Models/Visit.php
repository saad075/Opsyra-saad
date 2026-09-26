<?php

namespace App\Modules\Attendance\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** One planned visit on one day, and what the check-in/out recorded. */
class Visit extends Model
{
    use BelongsToTenant;

    public const OPEN = ['planned', 'in_progress'];

    protected $fillable = [
        'visit_location_id', 'user_id', 'visit_plan_id', 'date', 'planned_start', 'planned_end', 'status',
        'checked_in_at', 'check_in_latitude', 'check_in_longitude', 'check_in_accuracy_m', 'check_in_distance_m', 'check_in_within_range',
        'checked_out_at', 'check_out_latitude', 'check_out_longitude', 'check_out_accuracy_m', 'check_out_distance_m', 'check_out_within_range',
        'missed_checkout', 'worked_minutes', 'review_status', 'review_note', 'reviewed_by', 'reviewed_at', 'caregiver_note', 'cancel_reason', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'planned_start' => 'datetime',
            'planned_end' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
            'check_in_accuracy_m' => 'integer',
            'check_in_distance_m' => 'integer',
            'check_in_within_range' => 'boolean',
            'check_out_latitude' => 'float',
            'check_out_longitude' => 'float',
            'check_out_accuracy_m' => 'integer',
            'check_out_distance_m' => 'integer',
            'check_out_within_range' => 'boolean',
            'missed_checkout' => 'boolean',
            'worked_minutes' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function location()
    {
        return $this->belongsTo(VisitLocation::class, 'visit_location_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(VisitPlan::class, 'visit_plan_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function plannedMinutes(): int
    {
        return (int) floor(($this->planned_end->getTimestamp() - $this->planned_start->getTimestamp()) / 60);
    }

    /**
     * Minutes that count for the day and for pay: a completed visit that
     * wasn't rejected, and — if it was flagged — only once a coordinator
     * accepted it.
     */
    public function countableMinutes(): int
    {
        if ($this->status !== 'completed' || in_array($this->review_status, ['needs_review', 'rejected'], true)) {
            return 0;
        }

        return $this->worked_minutes;
    }
}
