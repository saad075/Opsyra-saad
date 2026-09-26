<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Adapted from SLMS360's StaffHolidayController (no permission checks).
 * A holiday applies to every branch unless one is chosen.
 */
class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['year' => ['nullable', 'integer', 'between:2000,2100']]);
        $year = $validated['year'] ?? (int) now()->format('Y');

        $data = Holiday::with('branch:id,name')
            ->whereBetween('date', ["{$year}-01-01", "{$year}-12-31"])
            ->orderBy('date')
            ->get();

        return response()->json(['status' => true, 'message' => 'Holidays', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-attendance');

        $holiday = Holiday::create($this->validateHoliday($request));

        return response()->json(['status' => true, 'message' => 'Holiday added.', 'data' => $holiday], 201);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-attendance');

        $request->validate(['id' => ['required', 'integer', ValidationRule::exists('holidays', 'id')->where('tenant_id', $request->user()->tenant_id)]]);
        $holiday = Holiday::findOrFail($request->id);
        $holiday->update($this->validateHoliday($request));

        return response()->json(['status' => true, 'message' => 'Holiday saved.', 'data' => $holiday]);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-attendance');

        $request->validate(['id' => ['required', 'integer', ValidationRule::exists('holidays', 'id')->where('tenant_id', $request->user()->tenant_id)]]);
        Holiday::findOrFail($request->id)->delete();

        return response()->json(['status' => true, 'message' => 'Holiday removed.']);
    }

    private function validateHoliday(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', ValidationRule::exists('branches', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);
    }
}
