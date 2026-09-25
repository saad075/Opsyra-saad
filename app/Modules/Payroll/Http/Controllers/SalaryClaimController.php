<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Exceptions\PayrollException;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Models\SalaryAdjustment;
use App\Modules\Payroll\Models\SalaryClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Adapted from SLMS360's Staff/SalaryClaimController — staff query a payslip,
 * HR resolves it. SLMS360 had no permission check on approve/reject, and
 * "approve" regenerated the already-approved salary in place. Here an
 * approved claim is settled with an adjustment on the next payslip, so
 * approved pay is never rewritten after the fact.
 */
class SalaryClaimController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-salaries');

        $data = SalaryClaim::with(['staff:id,name', 'payslip:id,period_start,period_end,net_pay,currency', 'resolvedBy:id,name'])
            ->where('status', $request->input('status', 'open'))
            ->orderBy('created_at')
            ->get();

        return response()->json(['status' => true, 'message' => 'Salary claims', 'data' => $data]);
    }

    public function mine(Request $request)
    {
        $data = SalaryClaim::with('payslip:id,period_start,period_end,net_pay,currency')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['status' => true, 'message' => 'My salary claims', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payslip_id' => ['required', 'integer', ValidationRule::exists('payslips', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $payslip = Payslip::findOrFail($validated['payslip_id']);

        if ($payslip->user_id !== $request->user()->id || $payslip->status !== 'approved') {
            throw new PayrollException('You can only query your own approved payslips.');
        }

        if (SalaryClaim::where('payslip_id', $payslip->id)->where('status', 'open')->exists()) {
            throw new PayrollException('There is already an open query on this payslip.');
        }

        $claim = SalaryClaim::create([
            'payslip_id' => $payslip->id,
            'user_id' => $request->user()->id,
            'reason' => $validated['reason'],
        ]);

        return response()->json(['status' => true, 'message' => 'Your query has been sent to HR.', 'data' => $claim], 201);
    }

    /**
     * Optionally settles the claim with a credit or debit on the next payslip.
     */
    public function approve(Request $request)
    {
        $this->authorize('manage-salaries');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('salary_claims', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'response' => ['required', 'string', 'max:2000'],
            'adjustment_type' => ['nullable', 'required_with:adjustment_amount', ValidationRule::in(SalaryAdjustment::TYPES)],
            'adjustment_amount' => ['nullable', 'numeric', 'gt:0', 'max:999999999999'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $claim = $this->lockOpen($validated['id']);
            $adjustment = null;

            if (! empty($validated['adjustment_amount'])) {
                $adjustment = SalaryAdjustment::create([
                    'user_id' => $claim->user_id,
                    'type' => $validated['adjustment_type'],
                    'amount' => $validated['adjustment_amount'],
                    'title' => 'Salary query settlement',
                    'label' => 'salary_claim',
                    'description' => "Settles salary query #{$claim->id} on payslip #{$claim->payslip_id}.",
                    'effective_date' => today(),
                    'created_by' => $request->user()->id,
                ]);
            }

            $claim->update([
                'status' => 'approved',
                'response' => $validated['response'],
                'salary_adjustment_id' => $adjustment?->id,
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);
        });

        return response()->json(['status' => true, 'message' => 'Salary query approved.']);
    }

    public function reject(Request $request)
    {
        $this->authorize('manage-salaries');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('salary_claims', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'response' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $this->lockOpen($validated['id'])->update([
                'status' => 'rejected',
                'response' => $validated['response'],
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);
        });

        return response()->json(['status' => true, 'message' => 'Salary query rejected.']);
    }

    private function lockOpen(int $id): SalaryClaim
    {
        $claim = SalaryClaim::whereKey($id)->lockForUpdate()->firstOrFail();

        if ($claim->status !== 'open') {
            throw new PayrollException('This query has already been resolved.');
        }

        return $claim;
    }
}
