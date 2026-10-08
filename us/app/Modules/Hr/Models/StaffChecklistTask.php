<?php

namespace App\Modules\Hr\Models;

use App\Models\User;
use App\Modules\Rbac\Models\Role;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StaffChecklistTask extends Model
{
    use BelongsToTenant;

    public const STATUSES = ['todo', 'in_progress', 'done', 'skipped'];

    public const CLOSED_STATUSES = ['done', 'skipped'];

    protected $fillable = [
        'staff_checklist_id',
        'title',
        'description',
        'assignee_user_id',
        'assignee_role_id',
        'due_date',
        'is_required',
        'sequence',
        'status',
        'note',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_required' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Named assignee, anyone holding the assigned role, or — for tasks that
     * resolved to nobody (e.g. "manager" for someone with no manager) —
     * nobody but HR.
     */
    public function isAssignedTo(User $user): bool
    {
        if ($this->assignee_user_id !== null && $this->assignee_user_id === $user->id) {
            return true;
        }

        return $this->assignee_role_id !== null
            && $user->roles->contains('id', $this->assignee_role_id);
    }

    public function checklist()
    {
        return $this->belongsTo(StaffChecklist::class, 'staff_checklist_id');
    }

    public function assigneeUser()
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function assigneeRole()
    {
        return $this->belongsTo(Role::class, 'assignee_role_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
