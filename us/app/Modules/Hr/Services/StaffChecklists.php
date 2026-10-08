<?php

namespace App\Modules\Hr\Services;

use App\Models\User;
use App\Modules\Hr\Exceptions\WorkflowStateException;
use App\Modules\Hr\Models\ChecklistTemplate;
use App\Modules\Hr\Models\ChecklistTemplateTask;
use App\Modules\Hr\Models\StaffChecklist;
use App\Modules\Hr\Models\StaffChecklistTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Onboarding/offboarding checklists — new, no SLMS360 equivalent. Designed
 * from the pattern Frappe HRMS, Horilla and Odoo share (template → a task
 * list per person → status derived from the tasks); see
 * docs/research/onboarding-offboarding.md. Layered on top of the L2 gate:
 * StaffOnboarding/StaffOffboarding call start() when a request is approved.
 */
class StaffChecklists
{
    /**
     * Returns null when there's nothing to create from — no template given
     * and no active default for the type. A checklist is a help, not a
     * precondition, so approval never fails for want of one.
     *
     * @param  array{staff_onboarding_request_id?: int, staff_offboard_request_id?: int}  $links
     */
    public function start(
        User $staff,
        string $type,
        mixed $anchorDate,
        ?int $templateId = null,
        ?User $createdBy = null,
        array $links = [],
    ): ?StaffChecklist {
        $template = $templateId
            ? ChecklistTemplate::where('type', $type)->where('is_active', true)->find($templateId)
            : ChecklistTemplate::defaultFor($type);

        if (! $template) {
            return null;
        }

        return DB::transaction(function () use ($staff, $type, $anchorDate, $template, $createdBy, $links) {
            // A new onboarding (e.g. a rehire) supersedes any earlier one
            // that was never finished; same for offboarding.
            StaffChecklist::where('user_id', $staff->id)
                ->where('type', $type)
                ->where('status', 'in_progress')
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $anchor = Carbon::parse($anchorDate ?? today())->startOfDay();

            $checklist = StaffChecklist::create([
                'user_id' => $staff->id,
                'type' => $type,
                'checklist_template_id' => $template->id,
                'anchor_date' => $anchor,
                'status' => 'in_progress',
                'created_by' => $createdBy?->id,
            ] + $links);

            foreach ($template->tasks as $templateTask) {
                $checklist->tasks()->create([
                    'title' => $templateTask->title,
                    'description' => $templateTask->description,
                    'assignee_user_id' => $this->resolveAssigneeUser($templateTask, $staff),
                    'assignee_role_id' => $templateTask->assignee_type === 'role' ? $templateTask->assignee_role_id : null,
                    'due_date' => $anchor->copy()->addDays($templateTask->due_offset_days),
                    'is_required' => $templateTask->is_required,
                    'sequence' => $templateTask->sequence,
                ]);
            }

            return $checklist;
        });
    }

    public function updateTask(StaffChecklistTask $task, User $actor, string $status, ?string $note = null): StaffChecklistTask
    {
        return DB::transaction(function () use ($task, $actor, $status, $note) {
            $checklist = StaffChecklist::whereKey($task->staff_checklist_id)->lockForUpdate()->firstOrFail();

            if ($checklist->status === 'cancelled') {
                throw new WorkflowStateException('This checklist has been cancelled.');
            }

            if ($status === 'skipped' && $task->is_required) {
                throw ValidationException::withMessages(['status' => 'A required task cannot be skipped.']);
            }

            $closing = in_array($status, StaffChecklistTask::CLOSED_STATUSES, true);

            $task->update([
                'status' => $status,
                'note' => $note ?? $task->note,
                'completed_by' => $closing ? $actor->id : null,
                'completed_at' => $closing ? now() : null,
            ]);

            $this->refreshStatus($checklist);

            return $task;
        });
    }

    public function addTask(StaffChecklist $checklist, array $data): StaffChecklistTask
    {
        return DB::transaction(function () use ($checklist, $data) {
            $checklist = StaffChecklist::whereKey($checklist->id)->lockForUpdate()->firstOrFail();

            if ($checklist->status === 'cancelled') {
                throw new WorkflowStateException('This checklist has been cancelled.');
            }

            $task = $checklist->tasks()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'assignee_user_id' => $data['assignee_user_id'] ?? null,
                'assignee_role_id' => $data['assignee_role_id'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'is_required' => $data['is_required'] ?? true,
                'sequence' => ($checklist->tasks()->max('sequence') ?? 0) + 1,
            ]);

            // Adding a task to a finished checklist reopens it.
            $this->refreshStatus($checklist);

            return $task;
        });
    }

    public function cancel(StaffChecklist $checklist): void
    {
        if ($checklist->status === 'cancelled') {
            throw new WorkflowStateException('This checklist is already cancelled.');
        }

        $checklist->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    /**
     * Used when the thing a checklist was for stops happening — e.g. an
     * approved future exit that HR then cancels.
     */
    public function cancelForOffboardRequest(int $offboardRequestId): void
    {
        StaffChecklist::where('staff_offboard_request_id', $offboardRequestId)
            ->where('status', '!=', 'cancelled')
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    /**
     * Completed once every task is done or skipped (Frappe's derived status,
     * minus its in-between "In Process" state — progress is shown as a
     * count instead). Reopens if a task goes back to todo.
     */
    private function refreshStatus(StaffChecklist $checklist): void
    {
        $open = $checklist->tasks()->whereNotIn('status', StaffChecklistTask::CLOSED_STATUSES)->exists();

        if (! $open && $checklist->status === 'in_progress') {
            $checklist->update(['status' => 'completed', 'completed_at' => now()]);
        } elseif ($open && $checklist->status === 'completed') {
            $checklist->update(['status' => 'in_progress', 'completed_at' => null]);
        }
    }

    /**
     * Resolved once, at creation — a later change of manager doesn't move
     * tasks already handed out (HR can reassign by adding a task). Someone
     * with no manager, or a named user who has since left, leaves the task
     * unassigned: HR sees and can complete those.
     */
    private function resolveAssigneeUser(ChecklistTemplateTask $templateTask, User $staff): ?int
    {
        return match ($templateTask->assignee_type) {
            'manager' => $staff->hierarchy_parent_id,
            'employee' => $staff->id,
            'user' => $templateTask->assigneeUser?->status === 'active' ? $templateTask->assignee_user_id : null,
            default => null,
        };
    }
}
