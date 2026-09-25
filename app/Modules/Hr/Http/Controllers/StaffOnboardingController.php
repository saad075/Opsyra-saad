<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Models\StaffOnboardingRequest;
use App\Modules\Hr\Services\StaffOnboarding;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Adapted from SLMS360's StaffController approveOnboarding/rejectOnboarding/
 * completeRejectedOnboarding/pendingOnboardApprovals/
 * rejectedOnboardingAwaitingNotification. Submission itself happens inside
 * StaffController::create() (and ::statusUpdate() for rehires); the state
 * machine lives in StaffOnboarding.
 *
 * SLMS360 scoped these by hand with ->where('client_id', ...) on every query;
 * here BelongsToTenant + RLS do it structurally, and the request id is also
 * validated against the caller's tenant for a clean 422.
 */
class StaffOnboardingController extends Controller
{
    public function __construct(private StaffOnboarding $onboarding) {}

    public function approve(Request $request)
    {
        $this->authorize('approve-hr-requests');

        $onboardingRequest = $this->findRequest($request);
        $this->onboarding->approve($onboardingRequest, $request->user());

        return response()->json([
            'status' => true,
            'message' => 'Onboarding approved and the staff account has been activated.',
        ]);
    }

    public function reject(Request $request)
    {
        $this->authorize('approve-hr-requests');

        $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        $onboardingRequest = $this->findRequest($request);
        $this->onboarding->reject($onboardingRequest, $request->user(), $request->reason);

        return response()->json([
            'status' => true,
            'message' => 'Onboarding request rejected. HR must notify the candidate, then mark it complete.',
        ]);
    }

    public function completeRejected(Request $request)
    {
        $this->authorize('manage-hr');

        $onboardingRequest = $this->findRequest($request);
        $this->onboarding->completeRejection($onboardingRequest, $request->user());

        return response()->json([
            'status' => true,
            'message' => 'Closed out. The staff record has been moved to offboarded.',
        ]);
    }

    public function pendingApprovals()
    {
        $this->authorize('approve-hr-requests');

        $data = StaffOnboardingRequest::with(['staff:id,name,designation,avatar,joined_at', 'requestedBy:id,name'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        return response()->json(['status' => true, 'message' => 'Pending onboarding approvals', 'data' => $data]);
    }

    public function rejectedAwaitingNotification()
    {
        $this->authorize('manage-hr');

        $data = StaffOnboardingRequest::with(['staff:id,name,designation,avatar', 'requestedBy:id,name', 'reviewedBy:id,name'])
            ->where('status', 'rejected')
            ->whereNull('completed_at')
            ->orderBy('reviewed_at')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Rejected onboarding requests awaiting candidate notification',
            'data' => $data,
        ]);
    }

    private function findRequest(Request $request): StaffOnboardingRequest
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('staff_onboarding_requests', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        return StaffOnboardingRequest::findOrFail($validated['id']);
    }
}
