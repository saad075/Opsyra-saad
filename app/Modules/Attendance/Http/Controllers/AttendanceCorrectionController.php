<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Exceptions\AttendanceException;
use App\Modules\Attendance\Models\AttendanceCorrection;
use App\Modules\Attendance\Services\AttendanceEditor;
use App\Modules\Attendance\Services\WorkCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Adapted from SLMS360's AttendanceClaimController, which approved claims
 * by raw id with no permission or tenant check. Here: a staff member asks
 * about their own days only; HR (attendance management) or the person's
 * own manager decides; nobody decides their own.
 */
class AttendanceCorrectionController extends Controller
{
    public function store(Request $request, WorkCalendar $calendar)
    {
        $user = $request->user();

        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:'.$calendar->today($user)->toDateString()],
            'requested_in' => ['nullable', 'date_format:H:i', 'required_without:requested_status'],
            'requested_out' => ['nullable', 'date_format:H:i'],
            'requested_status' => ['nullable', ValidationRule::in(AttendanceCorrection::REQUESTABLE_STATUSES)],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        if (AttendanceCorrection::where('user_id', $user->id)->whereDate('date', $validated['date'])->where('status', 'open')->exists()) {
            throw new AttendanceException('You already have an open request for this day.');
        }

        $correction = AttendanceCorrection::create($validated + ['user_id' => $user->id]);

        return response()->json(['status' => true, 'message' => 'Your request has been sent.', 'data' => $correction], 201);
    }

    public function mine(Request $request)
    {
        $data = AttendanceCorrection::with('resolvedBy:id,name')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('date')
            ->limit(100)
            ->get();

        return response()->json(['status' => true, 'message' => 'My requests', 'data' => $data]);
    }

    /** HR sees everyone's; a manager sees their own team's. */
    public function index(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate(['status' => ['nullable', 'in:open,approved,rejected']]);

        $data = AttendanceCorrection::with(['staff:id,name,designation,hierarchy_parent_id', 'resolvedBy:id,name'])
            ->where('status', $validated['status'] ?? 'open')
            ->when(! $user->can('manage-attendance'), fn ($q) => $q->whereHas('staff', fn ($s) => $s->where('hierarchy_parent_id', $user->id)))
            ->where('user_id', '!=', $user->id)
            ->orderBy('date')
            ->limit(200)
            ->get();

        return response()->json(['status' => true, 'message' => 'Attendance requests', 'data' => $data]);
    }

    public function approve(Request $request, AttendanceEditor $editor)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('attendance_corrections', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'response' => ['nullable', 'string', 'max:2000'],
            'leave_paid' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $request, $editor) {
            $correction = $this->lockOpen($validated['id']);
            $this->authorize('resolve-attendance-correction', $correction);

            $editor->applyCorrection($correction, $request->user(), $validated['leave_paid'] ?? null);

            $correction->update([
                'status' => 'approved',
                'response' => $validated['response'] ?? null,
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);
        });

        return response()->json(['status' => true, 'message' => 'Request approved and the day updated.']);
    }

    public function reject(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('attendance_corrections', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'response' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $correction = $this->lockOpen($validated['id']);
            $this->authorize('resolve-attendance-correction', $correction);

            $correction->update([
                'status' => 'rejected',
                'response' => $validated['response'],
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);
        });

        return response()->json(['status' => true, 'message' => 'Request declined.']);
    }

    private function lockOpen(int $id): AttendanceCorrection
    {
        $correction = AttendanceCorrection::with('staff')->whereKey($id)->lockForUpdate()->firstOrFail();

        if ($correction->status !== 'open') {
            throw new AttendanceException('This request has already been decided.');
        }

        return $correction;
    }
}
