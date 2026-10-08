<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Hr\Models\StaffOffboardRequest;
use App\Modules\Hr\Services\StaffOffboarding;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\ValidationException;

/**
 * Adapted from SLMS360's StaffController offboardStaff/approveOffboarding/
 * rejectOffboarding/pendingOffboardApprovals/cancelOffboarding; the state
 * machine lives in StaffOffboarding.
 *
 * Security fixes made while porting: SLMS360's offboardStaff and
 * cancelOffboarding checked **no permission at all** — any authenticated
 * staff member could offboard anyone in their organisation, including
 * themselves. Both now require manage-staff-member on the target, and
 * self-offboarding is refused. new_manager_id was validated with a global
 * exists:users,id (any tenant, any status); it's now tenant-scoped, active
 * only, and can't be the leaver.
 */
class StaffOffboardingController extends Controller
{
    public function __construct(private StaffOffboarding $offboarding) {}

    public function offboard(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'last_working_day' => ['required', 'date'],
            'departure_reason_id' => [
                'required', 'integer',
                ValidationRule::exists('departure_reasons', 'id')->where('tenant_id', $tenantId)->where('is_active', true),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
            'new_manager_id' => [
                'nullable', 'integer', 'different:id',
                ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)->where('status', 'active'),
            ],
            // Which offboarding checklist to start on approval; omitted = the tenant's default.
            'checklist_template_id' => [
                'nullable', 'integer',
                ValidationRule::exists('checklist_templates', 'id')->where('tenant_id', $tenantId)->where('type', 'offboarding')->where('is_active', true),
            ],
        ]);

        $staff = User::findOrFail($validated['id']);

        $this->authorize('manage-staff-member', $staff);

        if (DepartureReason::find($validated['departure_reason_id'])->requires_note && blank($validated['note'] ?? null)) {
            throw ValidationException::withMessages(['note' => 'Please add a note for this reason.']);
        }

        $offboardRequest = $this->offboarding->submit($staff, $request->user(), $validated);

        if ($offboardRequest->status === 'approved') {
            return response()->json([
                'status' => true,
                'message' => $offboardRequest->last_working_day->lte(today())
                    ? 'Staff member has been offboarded.'
                    : 'Offboarding scheduled for '.$offboardRequest->last_working_day->format('d-M-Y').'.',
                'data' => ['id' => $offboardRequest->id, 'requires_approval' => false],
            ], 201);
        }

        return response()->json([
            'status' => true,
            'message' => 'Offboarding request submitted. It will take effect once an L2 approver reviews it.',
            'data' => ['id' => $offboardRequest->id, 'requires_approval' => true],
        ], 201);
    }

    public function approve(Request $request)
    {
        $this->authorize('approve-hr-requests');

        $this->offboarding->approve($this->findRequest($request), $request->user());

        return response()->json(['status' => true, 'message' => 'Offboarding approved and applied.']);
    }

    public function reject(Request $request)
    {
        $this->authorize('approve-hr-requests');

        $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        $this->offboarding->reject($this->findRequest($request), $request->user(), $request->reason);

        return response()->json(['status' => true, 'message' => 'Offboarding request rejected.']);
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $staff = User::findOrFail($validated['id']);

        $this->authorize('manage-staff-member', $staff);

        $this->offboarding->cancel($staff, $request->user());

        return response()->json(['status' => true, 'message' => 'Offboarding has been cancelled.']);
    }

    public function pendingApprovals()
    {
        $this->authorize('approve-hr-requests');

        $data = StaffOffboardRequest::with([
            'staff:id,name,designation,avatar',
            'requestedBy:id,name',
            'newManager:id,name',
            'departureReason:id,name',
        ])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        return response()->json(['status' => true, 'message' => 'Pending offboarding approvals', 'data' => $data]);
    }

    private function findRequest(Request $request): StaffOffboardRequest
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('staff_offboard_requests', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        return StaffOffboardRequest::findOrFail($validated['id']);
    }
}
