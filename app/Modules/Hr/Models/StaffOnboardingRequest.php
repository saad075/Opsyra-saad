<?php

namespace App\Modules\Hr\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StaffOnboardingRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'requested_by',
        'status',
        'checklist_template_id',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
