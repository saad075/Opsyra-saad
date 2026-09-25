<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Hr\Models\StaffChecklist;
use App\Modules\Hr\Models\StaffChecklistTask;
use App\Modules\Hr\Services\StaffChecklists;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * New — no SLMS360 equivalent. Checklists are normally started by the
 * approval gate (StaffOnboarding/StaffOffboarding); start() here is for
 * applying one by hand, e.g. to staff who joined before checklists existed.
 */
class StaffChecklistController extends Controller
{
    public function __construct(private StaffChecklists $checklists) {}

    /**
     * HR overview, with progress as counts rather than a stored percentage.
     */
    public function index(Request $request)
    {
        $this->authorize('manage-hr');

        $data = StaffChecklist::with(['staff:id,name,designation,avatar', 'template:id,name'])
            ->withCount([
                'tasks',
                'tasks as closed_tasks_count' => fn ($q) => $q->whereIn('status', StaffChecklistTask::CLOSED_STATUSES),
                'tasks as overdue_tasks_count' => fn ($q) => $q->whereNotIn('status', StaffChecklistTask::CLOSED_STATUSES)->whereDate('due_date', '<', today()),
            ])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->where('status', $request->input('status', 'in_progress'))
            ->orderBy('anchor_date')
            ->get();

        return response()->json(['status' => true, 'message' => 'Staff checklists', 'data' => $data]);
    }

    public function show(Request $request)
    {
        $checklist = $this->findChecklist($request);

        $this->authorize('view-staff-checklist', $checklist);

        $checklist->load([
            'staff:id,name,designation,avatar',
            'template:id,name',
            'tasks.assigneeUser:id,name',
            'tasks.assigneeRole:id,name',
            'tasks.completedBy:id,name',
        ]);

        return response()->json(['status' => true, 'message' => 'Staff checklist', 'data' => $checklist]);
    }

    public function start(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'type' => ['required', ValidationRule::in(['onboarding', 'offboarding'])],
            'checklist_template_id' => [
                'nullable', 'integer',
                ValidationRule::exists('checklist_templates', 'id')->where('tenant_id', $tenantId)->where('type', $request->type)->where('is_active', true),
            ],
            'anchor_date' => ['nullable', 'date'],
        ]);

        $staff = User::findOrFail($validated['user_id']);

        $this->authorize('manage-staff-member', $staff);

        $anchor = $validated['anchor_date']
            ?? ($validated['type'] === 'onboarding' ? $staff->joined_at : $staff->lefted_at)
            ?? today();

        $checklist = $this->checklists->start($staff, $validated['type'], $anchor, $validated['checklist_template_id'] ?? null, $request->user());

        if (! $checklist) {
            return response()->json([
                'status' => false,
                'message' => 'There is no active default template for this type. Choose a template or create one first.',
            ], 422);
        }

        return response()->json(['status' => true, 'message' => 'Checklist started.', 'data' => ['id' => $checklist->id]], 201);
    }

    public function addTask(Request $request)
    {
        $this->authorize('manage-hr');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'staff_checklist_id' => ['required', 'integer', ValidationRule::exists('staff_checklists', 'id')->where('tenant_id', $tenantId)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assignee_user_id' => ['nullable', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)->where('status', 'active')],
            'assignee_role_id' => ['nullable', 'integer', ValidationRule::exists('roles', 'id')->where('tenant_id', $tenantId)],
            'due_date' => ['nullable', 'date'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $task = $this->checklists->addTask(StaffChecklist::findOrFail($validated['staff_checklist_id']), $validated);

        return response()->json(['status' => true, 'message' => 'Task added.', 'data' => ['id' => $task->id]], 201);
    }

    public function cancel(Request $request)
    {
        $this->authorize('manage-hr');

        $this->checklists->cancel($this->findChecklist($request));

        return response()->json(['status' => true, 'message' => 'Checklist cancelled.']);
    }

    /**
     * Open tasks for the caller — named on the task, or holding its role —
     * across every checklist still in progress, soonest due first.
     */
    public function myTasks(Request $request)
    {
        $user = $request->user();
        $roleIds = $user->roles->pluck('id');

        $data = StaffChecklistTask::with(['checklist:id,user_id,type,anchor_date', 'checklist.staff:id,name,designation,avatar'])
            ->whereHas('checklist', fn ($q) => $q->where('status', 'in_progress'))
            ->whereNotIn('status', StaffChecklistTask::CLOSED_STATUSES)
            ->where(fn ($q) => $q->where('assignee_user_id', $user->id)->orWhereIn('assignee_role_id', $roleIds))
            ->orderByRaw('due_date IS NULL, due_date')
            ->get();

        return response()->json(['status' => true, 'message' => 'My checklist tasks', 'data' => $data]);
    }

    public function updateTask(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('staff_checklist_tasks', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'status' => ['required', ValidationRule::in(StaffChecklistTask::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $task = StaffChecklistTask::findOrFail($validated['id']);

        $this->authorize('complete-checklist-task', $task);

        $this->checklists->updateTask($task, $request->user(), $validated['status'], $validated['note'] ?? null);

        return response()->json(['status' => true, 'message' => 'Task updated.']);
    }

    private function findChecklist(Request $request): StaffChecklist
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('staff_checklists', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        return StaffChecklist::findOrFail($validated['id']);
    }
}
