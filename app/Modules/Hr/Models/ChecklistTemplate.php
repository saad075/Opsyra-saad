<?php

namespace App\Modules\Hr\Models;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class ChecklistTemplate extends Model
{
    use BelongsToTenant;

    public const TYPES = ['onboarding', 'offboarding'];

    protected $fillable = [
        'type',
        'name',
        'description',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tasks()
    {
        return $this->hasMany(ChecklistTemplateTask::class)->orderBy('sequence')->orderBy('id');
    }

    public static function defaultFor(string $type): ?self
    {
        return static::where('type', $type)->where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * A generic starting point each tenant edits to fit — only manager/
     * employee assignees, since a brand-new tenant has no roles or named
     * people to point tasks at yet.
     */
    public static function starterTemplates(): array
    {
        return [
            [
                'type' => 'onboarding',
                'name' => 'Standard onboarding',
                'tasks' => [
                    ['title' => 'Prepare equipment and system accounts', 'assignee_type' => 'manager', 'due_offset_days' => -3],
                    ['title' => 'Welcome and team introductions', 'assignee_type' => 'manager', 'due_offset_days' => 0],
                    ['title' => 'Complete personal and bank details', 'assignee_type' => 'employee', 'due_offset_days' => 0],
                    ['title' => 'Read and acknowledge company policies', 'assignee_type' => 'employee', 'due_offset_days' => 7],
                    ['title' => 'First-month check-in', 'assignee_type' => 'manager', 'due_offset_days' => 30, 'is_required' => false],
                ],
            ],
            [
                'type' => 'offboarding',
                'name' => 'Standard offboarding',
                'tasks' => [
                    ['title' => 'Hand over work and documents', 'assignee_type' => 'employee', 'due_offset_days' => -5],
                    ['title' => 'Exit interview', 'assignee_type' => 'manager', 'due_offset_days' => -2, 'is_required' => false],
                    ['title' => 'Collect equipment, keys and ID card', 'assignee_type' => 'manager', 'due_offset_days' => 0],
                    ['title' => 'Remove system access', 'assignee_type' => 'manager', 'due_offset_days' => 0],
                ],
            ],
        ];
    }

    /**
     * Idempotent: skips a type that already has any template, so a tenant
     * who deleted or replaced the starter doesn't get it back.
     */
    public static function seedDefaultsFor(Tenant $tenant): void
    {
        app(TenantContext::class)->runAs($tenant->id, function () {
            foreach (static::starterTemplates() as $starter) {
                if (static::where('type', $starter['type'])->exists()) {
                    continue;
                }

                $template = static::create([
                    'type' => $starter['type'],
                    'name' => $starter['name'],
                    'is_default' => true,
                ]);

                foreach ($starter['tasks'] as $i => $task) {
                    $template->tasks()->create($task + ['sequence' => $i + 1]);
                }
            }
        });
    }
}
