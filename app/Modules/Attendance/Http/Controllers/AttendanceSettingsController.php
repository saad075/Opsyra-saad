<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Support\AttendancePolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * The company's attendance rules, and how each person's attendance is
 * tracked (a weekly schedule, client visits, or not at all).
 */
class AttendanceSettingsController extends Controller
{
    public function policy()
    {
        $this->authorize('manage-attendance');

        return response()->json(['status' => true, 'message' => 'Attendance rules', 'data' => AttendancePolicy::all()]);
    }

    public function updatePolicy(Request $request)
    {
        $this->authorize('manage-attendance');

        $validated = $request->validate(AttendancePolicy::RULES);

        if (($validated['absent_below_percent'] ?? AttendancePolicy::get('absent_below_percent')) > ($validated['half_day_below_percent'] ?? AttendancePolicy::get('half_day_below_percent'))) {
            return response()->json(['status' => false, 'message' => 'The absent threshold can’t be above the half-day threshold.', 'errors' => ['absent_below_percent' => ['The absent threshold can’t be above the half-day threshold.']]], 422);
        }

        return response()->json(['status' => true, 'message' => 'Attendance rules saved. They apply to days worked out from now and to pay runs made from now.', 'data' => AttendancePolicy::update($validated)]);
    }

    public function setMode(Request $request)
    {
        $this->authorize('manage-attendance');

        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:500'],
            'user_ids.*' => ['integer', 'distinct', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'attendance_mode' => ['required', 'in:schedule,visits,not_tracked'],
        ]);

        User::whereIn('id', $validated['user_ids'])->update(['attendance_mode' => $validated['attendance_mode']]);

        return response()->json(['status' => true, 'message' => 'Updated.']);
    }
}
