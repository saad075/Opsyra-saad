<?php

namespace App\Modules\Hr\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StaffOffboardRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'requested_by',
        'last_working_day',
        'departure_reason_id',
        'note',
        'new_manager_id',
        'direct_reports_count',
        'status',
        'checklist_template_id',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'last_working_day' => 'date',
            'reviewed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * What ends up in users.left_reason — the reason's name, plus the note
     * when there is one (replaces SLMS360's "Other: <note>" string convention).
     */
    public function leftReasonText(): string
    {
        $name = $this->departureReason->name;

        return $this->note ? "{$name}: {$this->note}" : $name;
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function departureReason()
    {
        return $this->belongsTo(DepartureReason::class);
    }

    public function newManager()
    {
        return $this->belongsTo(User::class, 'new_manager_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
