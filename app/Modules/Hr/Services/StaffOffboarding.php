<?php

namespace App\Modules\Hr\Services;

use App\Models\User;
use App\Modules\Hr\Exceptions\ManagerReassignmentRequired;
use App\Modules\Hr\Exceptions\WorkflowStateException;
use App\Modules\Hr\Models\StaffOffboardRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The L2 offboarding gate, adapted from SLMS360's StaffController
 * offboardStaff/applyOffboarding/approveOffboarding/rejectOffboarding/
 * cancelOffboarding and the staff:process-offboarding command.
 *
 * A last working day of today or earlier applies on approval; a future one
 * keeps the person fully active until the nightly run flips them the day
 * after it (both as SLMS360). Active direct reports must be handed to a
 * replacement manager — uniquely among the HR systems we compared, and kept
 * for that reason (docs/research/onboarding-offboarding.md).
 */
class StaffOffboarding
{
    public function __construct(private StaffChecklists $checklists) {}

    /**
     * @param  array{last_working_day: string, departure_reason_id: int, note?: ?string, new_manager_id?: ?int, checklist_template_id?: ?int}  $data
     */
    public function submit(User $staff, User $requester, array $data): StaffOffboardRequest
    {
        return DB::transaction(function () use ($staff, $requester, $data) {
            // Row lock on the staff member so two simultaneous submissions
            // can't both pass the "nothing already open" check below.
            $staff = User::whereKey($staff->id)->lockForUpdate()->firstOrFail();

            if ($staff->id === $requester->id) {
                throw new WorkflowStateException('You cannot offboard yourself.');
            }

            if (! in_array($staff->status, ['active', 'pending'], true)) {
                throw new WorkflowStateException('Only an active or not-yet-started staff member can be offboarded.');
            }

            if ($this->hasOpenOffboarding($staff)) {
                throw new WorkflowStateException('This staff member already has an offboarding pending or scheduled.');
            }

            $directReportsCount = $this->activeDirectReports($staff)->count();

            if ($directReportsCount > 0 && empty($data['new_manager_id'])) {
                throw new ManagerReassignmentRequired($directReportsCount);
            }

            $request = StaffOffboardRequest::create([
                'user_id' => $staff->id,
                'requested_by' => $requester->id,
                'last_working_day' => $data['last_working_day'],
                'departure_reason_id' => $data['departure_reason_id'],
                'note' => $data['note'] ?? null,
                'new_manager_id' => $data['new_manager_id'] ?? null,
                'direct_reports_count' => $directReportsCount,
                'status' => 'pending',
                'checklist_template_id' => $data['checklist_template_id'] ?? null,
            ]);

            if ($requester->can('approve-hr-requests')) {
                $this->approve($request, $requester);
                $request->refresh();
            }

            return $request;
        });
    }

    public function approve(StaffOffboardRequest $request, User $reviewer): void
    {
        DB::transaction(function () use ($request, $reviewer) {
            $locked = $this->lockPending($request);

            // The replacement manager was valid when the request was made,
            // but may have left or been deactivated while it waited.
            if ($locked->new_manager_id && $locked->newManager?->status !== 'active') {
                throw new WorkflowStateException('The chosen replacement manager is no longer active. Reject this request and submit a new one.');
            }

            $this->apply($locked);

            $locked->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $this->checklists->start(
                $locked->staff,
                'offboarding',
                $locked->last_working_day,
                $locked->checklist_template_id,
                $reviewer,
                ['staff_offboard_request_id' => $locked->id],
            );
        });
    }

    /**
     * The staff member is never touched by a rejection.
     */
    public function reject(StaffOffboardRequest $request, User $reviewer, string $reason): void
    {
        DB::transaction(function () use ($request, $reviewer, $reason) {
            $this->lockPending($request)->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    /**
     * Withdraws a request still awaiting approval, or undoes an approved
     * exit that hasn't taken effect yet. SLMS360 only handled the second
     * case, and left the request row saying 'approved' afterwards.
     */
    public function cancel(User $staff, User $actor): void
    {
        DB::transaction(function () use ($staff, $actor) {
            $staff = User::whereKey($staff->id)->lockForUpdate()->firstOrFail();

            $pending = StaffOffboardRequest::where('user_id', $staff->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($pending) {
                $pending->update(['status' => 'cancelled', 'cancelled_by' => $actor->id, 'cancelled_at' => now()]);

                return;
            }

            if (! $this->hasScheduledExit($staff)) {
                throw new WorkflowStateException('There is no pending or upcoming offboarding to cancel for this staff member.');
            }

            $staff->update([
                'lefted_at' => null,
                'left_reason' => null,
                'departure_reason_id' => null,
                'offboarding_new_manager_id' => null,
            ]);

            $approved = StaffOffboardRequest::where('user_id', $staff->id)
                ->where('status', 'approved')
                ->latest('id')
                ->first();

            if ($approved) {
                $approved->update(['status' => 'cancelled', 'cancelled_by' => $actor->id, 'cancelled_at' => now()]);
                $this->checklists->cancelForOffboardRequest($approved->id);
            }
        });
    }

    /**
     * Nightly: everyone whose last working day has passed moves to
     * 'offboarded'. Runs under a single tenant's context — the command loops
     * tenants. 'pending' is included because someone can leave before a
     * future join date ever arrives.
     */
    public function processDue(): int
    {
        $due = User::whereIn('status', ['active', 'pending'])
            ->whereNotNull('lefted_at')
            ->whereDate('lefted_at', '<', today())
            ->get();

        foreach ($due as $user) {
            DB::transaction(function () use ($user) {
                // Falls back to the leaver's own manager if none was chosen
                // (e.g. reports were added after the exit was scheduled).
                $this->reassignDirectReports($user, $user->offboarding_new_manager_id ?? $user->hierarchy_parent_id);
                $this->deactivate($user);
            });
        }

        return $due->count();
    }

    private function apply(StaffOffboardRequest $request): void
    {
        $staff = $request->staff;
        $lastWorkingDay = Carbon::parse($request->last_working_day)->startOfDay();
        $isImmediate = $lastWorkingDay->lte(today());

        $staff->fill([
            'lefted_at' => $lastWorkingDay,
            'left_reason' => $request->leftReasonText(),
            'departure_reason_id' => $request->departure_reason_id,
        ]);

        if ($isImmediate) {
            $this->reassignDirectReports($staff, $request->new_manager_id ?? $staff->hierarchy_parent_id);
            $this->deactivate($staff);
        } else {
            // Reports stay with the outgoing manager until they actually leave.
            $staff->offboarding_new_manager_id = $request->new_manager_id;
            $staff->save();
        }
    }

    private function deactivate(User $staff): void
    {
        $staff->status = 'offboarded';
        $staff->offboarding_new_manager_id = null;
        $staff->save();

        // SLMS360 left an offboarded user's API tokens working.
        $staff->tokens()->delete();
    }

    /**
     * Moves the leaver's active reports to $newManagerId. If the new manager
     * is one of those reports (promoting from within the team), they move up
     * to the leaver's own manager instead of ending up reporting to themselves.
     */
    private function reassignDirectReports(User $leaver, ?int $newManagerId): void
    {
        $reports = $this->activeDirectReports($leaver);

        if ($newManagerId !== null) {
            (clone $reports)->where('id', $newManagerId)->update(['hierarchy_parent_id' => $leaver->hierarchy_parent_id]);
            $reports->where('id', '!=', $newManagerId);
        }

        $reports->update(['hierarchy_parent_id' => $newManagerId]);
    }

    private function activeDirectReports(User $staff)
    {
        return User::where('hierarchy_parent_id', $staff->id)->where('status', 'active');
    }

    private function hasOpenOffboarding(User $staff): bool
    {
        return $this->hasScheduledExit($staff)
            || StaffOffboardRequest::where('user_id', $staff->id)->where('status', 'pending')->exists();
    }

    private function hasScheduledExit(User $staff): bool
    {
        return in_array($staff->status, ['active', 'pending'], true)
            && $staff->lefted_at !== null
            && Carbon::parse($staff->lefted_at)->startOfDay()->gt(today());
    }

    private function lockPending(StaffOffboardRequest $request): StaffOffboardRequest
    {
        $locked = StaffOffboardRequest::whereKey($request->id)->lockForUpdate()->first();

        if (! $locked || $locked->status !== 'pending') {
            throw new WorkflowStateException('This request is no longer pending.');
        }

        return $locked;
    }
}
