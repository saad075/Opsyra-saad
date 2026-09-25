<?php

namespace App\Modules\Hr\Services;

use App\Models\User;
use App\Modules\Hr\Exceptions\WorkflowStateException;
use App\Modules\Hr\Models\StaffOnboardingRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The L2 onboarding gate, adapted from SLMS360's StaffController
 * create/applyOnboarding/approveOnboarding/rejectOnboarding/
 * completeRejectedOnboarding. A staff record sits at 'pending_approval'
 * until someone holding HRMS-Approval-L2 approves it — or immediately, if
 * the person submitting already holds it (they ARE the approval).
 *
 * Every state change locks the request row first, so two approvers clicking
 * at once can't both apply it (SLMS360 read-then-wrote without a lock).
 */
class StaffOnboarding
{
    public function submit(User $staff, User $requester): StaffOnboardingRequest
    {
        $staff->update(['status' => 'pending_approval']);

        $request = StaffOnboardingRequest::create([
            'user_id' => $staff->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
        ]);

        if ($requester->can('approve-hr-requests')) {
            $this->approve($request, $requester);
            $request->refresh();
        }

        return $request;
    }

    public function approve(StaffOnboardingRequest $request, User $reviewer): void
    {
        DB::transaction(function () use ($request, $reviewer) {
            $locked = $this->lockInState($request, 'pending', 'This request is no longer pending.');

            $staff = $locked->staff;
            $staff->update(['status' => self::statusForJoinDate($staff->joined_at)]);

            $locked->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    /**
     * The staff record is deliberately left at 'pending_approval' — HR still
     * has to tell the candidate, and only completeRejection() closes it out.
     */
    public function reject(StaffOnboardingRequest $request, User $reviewer, string $reason): void
    {
        DB::transaction(function () use ($request, $reviewer, $reason) {
            $locked = $this->lockInState($request, 'pending', 'This request is no longer pending.');

            $locked->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function completeRejection(StaffOnboardingRequest $request, User $actor): void
    {
        DB::transaction(function () use ($request, $actor) {
            $locked = $this->lockInState($request, 'rejected', 'This request is not awaiting candidate notification.');

            if ($locked->completed_at !== null) {
                throw new WorkflowStateException('This request is not awaiting candidate notification.');
            }

            $locked->update(['completed_by' => $actor->id, 'completed_at' => now()]);

            $locked->staff->update([
                'status' => 'offboarded',
                'lefted_at' => now()->startOfDay(),
                'left_reason' => 'Onboarding rejected: '.$locked->rejection_reason,
            ]);
        });
    }

    /**
     * Derived at approval time rather than frozen at submission (SLMS360's
     * intended_status), so a request approved after the join date has
     * already passed activates straight away instead of waiting for the
     * nightly staff:activate-pending run.
     */
    public static function statusForJoinDate(mixed $joinedAt): string
    {
        return Carbon::parse($joinedAt)->startOfDay()->gt(today()) ? 'pending' : 'active';
    }

    private function lockInState(StaffOnboardingRequest $request, string $status, string $message): StaffOnboardingRequest
    {
        $locked = StaffOnboardingRequest::whereKey($request->id)->lockForUpdate()->first();

        if (! $locked || $locked->status !== $status) {
            throw new WorkflowStateException($message);
        }

        return $locked;
    }
}
