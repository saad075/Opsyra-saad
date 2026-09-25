<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Models\SalaryAdjustment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Adapted from SLMS360's HR/Staff/StaffAdjustmentController: one-off
 * credits (bonus, arrears) and debits (advance recovery) that land on the
 * next payslip whose period contains the effective date. An adjustment
 * already applied to a payslip can't be changed or deleted — correct it
 * with a new opposite adjustment.
 */
class SalaryAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-salary-adjustments');

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'state' => ['nullable', ValidationRule::in(['open', 'applied'])],
        ]);

        $data = SalaryAdjustment::with(['staff:id,name', 'createdBy:id,name'])
            ->when($validated['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when(($validated['state'] ?? null) === 'open', fn ($q) => $q->whereNull('payslip_id'))
            ->when(($validated['state'] ?? null) === 'applied', fn ($q) => $q->whereNotNull('payslip_id'))
            ->orderByDesc('effective_date')
            ->get();

        return response()->json(['status' => true, 'message' => 'Salary adjustments', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-salary-adjustments');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'type' => ['required', ValidationRule::in(SalaryAdjustment::TYPES)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'effective_date' => ['required', 'date'],
        ]);

        $adjustment = SalaryAdjustment::create($validated + ['created_by' => $request->user()->id]);

        return response()->json(['status' => true, 'message' => 'Adjustment added.', 'data' => $adjustment], 201);
    }

    public function destroy(Request $request)
    {
        $this->authorize('manage-salary-adjustments');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('salary_adjustments', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $adjustment = SalaryAdjustment::findOrFail($validated['id']);

        if ($adjustment->payslip_id !== null) {
            return response()->json([
                'status' => false,
                'message' => 'This adjustment is already on a payslip. Add an opposite adjustment to correct it.',
            ], 409);
        }

        $adjustment->delete();

        return response()->json(['status' => true, 'message' => 'Adjustment deleted.']);
    }
}
