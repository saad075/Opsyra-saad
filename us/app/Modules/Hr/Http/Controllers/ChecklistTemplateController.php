<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Models\ChecklistTemplate;
use App\Modules\Hr\Models\ChecklistTemplateTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * New — no SLMS360 equivalent. Onboarding/offboarding checklist templates.
 * Reading is open to HR only; every change needs manage-hr. Checklists copy
 * their tasks at creation, so editing or deleting a template never touches
 * a checklist already under way.
 */
class ChecklistTemplateController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-hr');

        $data = ChecklistTemplate::with(['tasks.assigneeRole:id,name', 'tasks.assigneeUser:id,name'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
            ->orderBy('type')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return response()->json(['status' => true, 'message' => 'Checklist templates', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-hr');

        $validated = $this->validateTemplate($request);

        $template = DB::transaction(function () use ($validated) {
            $template = new ChecklistTemplate;
            $this->save($template, $validated);

            return $template;
        });

        return response()->json([
            'status' => true,
            'message' => 'Checklist template created.',
            'data' => $template->load('tasks'),
        ], 201);
    }

    /**
     * Replaces the task list wholesale — the UI edits a template as one form.
     */
    public function update(Request $request)
    {
        $this->authorize('manage-hr');

        $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('checklist_templates', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $template = ChecklistTemplate::findOrFail($request->id);
        $validated = $this->validateTemplate($request, $template);

        DB::transaction(fn () => $this->save($template, $validated));

        return response()->json([
            'status' => true,
            'message' => 'Checklist template updated.',
            'data' => $template->fresh('tasks'),
        ]);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-hr');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('checklist_templates', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        ChecklistTemplate::findOrFail($validated['id'])->delete();

        return response()->json(['status' => true, 'message' => 'Checklist template deleted.']);
    }

    private function validateTemplate(Request $request, ?ChecklistTemplate $existing = null): array
    {
        $tenantId = $request->user()->tenant_id;

        return $request->validate([
            'type' => ['required', ValidationRule::in(ChecklistTemplate::TYPES)],
            'name' => [
                'required', 'string', 'max:255',
                ValidationRule::unique('checklist_templates')
                    ->where('tenant_id', $tenantId)
                    ->where('type', $request->type)
                    ->ignore($existing?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'tasks' => ['required', 'array', 'min:1', 'max:100'],
            'tasks.*.title' => ['required', 'string', 'max:255'],
            'tasks.*.description' => ['nullable', 'string', 'max:2000'],
            'tasks.*.assignee_type' => ['required', ValidationRule::in(ChecklistTemplateTask::ASSIGNEE_TYPES)],
            'tasks.*.assignee_role_id' => [
                'nullable', 'required_if:tasks.*.assignee_type,role', 'integer',
                ValidationRule::exists('roles', 'id')->where('tenant_id', $tenantId),
            ],
            'tasks.*.assignee_user_id' => [
                'nullable', 'required_if:tasks.*.assignee_type,user', 'integer',
                ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)->where('status', 'active'),
            ],
            'tasks.*.due_offset_days' => ['nullable', 'integer', 'between:-365,365'],
            'tasks.*.is_required' => ['nullable', 'boolean'],
        ]);
    }

    private function save(ChecklistTemplate $template, array $validated): void
    {
        $isDefault = $validated['is_default'] ?? $template->is_default ?? false;

        // Only one default per type (also enforced by a partial unique index).
        if ($isDefault) {
            ChecklistTemplate::where('type', $validated['type'])
                ->where('is_default', true)
                ->when($template->exists, fn ($q) => $q->whereKeyNot($template->id))
                ->update(['is_default' => false]);
        }

        $template->fill([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_default' => $isDefault,
            'is_active' => $validated['is_active'] ?? $template->is_active ?? true,
        ])->save();

        $template->tasks()->delete();

        foreach ($validated['tasks'] as $i => $task) {
            $template->tasks()->create([
                'title' => $task['title'],
                'description' => $task['description'] ?? null,
                'assignee_type' => $task['assignee_type'],
                'assignee_role_id' => $task['assignee_type'] === 'role' ? $task['assignee_role_id'] : null,
                'assignee_user_id' => $task['assignee_type'] === 'user' ? $task['assignee_user_id'] : null,
                'due_offset_days' => $task['due_offset_days'] ?? 0,
                'is_required' => $task['is_required'] ?? true,
                'sequence' => $i + 1,
            ]);
        }
    }
}
