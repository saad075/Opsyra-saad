<?php

namespace App\Modules\Hr\Models;

use App\Models\User;
use App\Modules\Rbac\Models\Role;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ChecklistTemplateTask extends Model
{
    use BelongsToTenant;

    public const ASSIGNEE_TYPES = ['manager', 'employee', 'role', 'user'];

    protected $fillable = [
        'checklist_template_id',
        'title',
        'description',
        'assignee_type',
        'assignee_role_id',
        'assignee_user_id',
        'due_offset_days',
        'is_required',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    public function assigneeRole()
    {
        return $this->belongsTo(Role::class, 'assignee_role_id');
    }

    public function assigneeUser()
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }
}
