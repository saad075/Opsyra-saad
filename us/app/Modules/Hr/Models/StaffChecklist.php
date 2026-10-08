<?php

namespace App\Modules\Hr\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StaffChecklist extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'type',
        'checklist_template_id',
        'staff_onboarding_request_id',
        'staff_offboard_request_id',
        'anchor_date',
        'status',
        'created_by',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'anchor_date' => 'date',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    public function tasks()
    {
        return $this->hasMany(StaffChecklistTask::class)->orderBy('sequence')->orderBy('id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
