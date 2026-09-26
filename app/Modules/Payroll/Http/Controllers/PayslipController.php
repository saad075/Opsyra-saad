<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Services\PayslipApprovals;
use App\Modules\Payroll\Services\PayslipGenerator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Adapted from SLMS360's SalaryController list/slip/approve/regenerate/
 * regenerateBulk/carryForward/pendingCheckNumbers/saveCheckNumber/
 * getCurrentSalary/getMonthWiseSalary. slip() there returned any payslip by
 * id to anyone; here staff see only their own, and only once approved.
 */
class PayslipController extends Controller
{
    private const RELEASED = ['approved', 'carried_forward'];

    public function __construct(private PayslipApprovals $approvals) {}

    public function index(Request $request)
    {
        $this->authorize('view-payroll');

        $validated = $request->validate([
            'pay_run_id' => ['nullable', 'integer', ValidationRule::exists('pay_runs', 'id')->where('tenant_id', $request->user()->tenant_id)],
            'status' => ['nullable', ValidationRule::in(['draft', 'l1_approved', 'approved', 'carried_forward'])],
        ]);

        $data = Payslip::with('staff:id,name,designation')
            ->when($validated['pay_run_id'] ?? null, fn ($q, $id) => $q->where('pay_run_id', $id))
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('period_start')
            ->orderBy('user_id')
            ->get();

        return response()->json(['status' => true, 'message' => 'Payslips', 'data' => $data]);
    }

    public function show(Request $request)
    {
        $payslip = $this->find($request);

        $isOwnReleased = $payslip->user_id === $request->user()->id && in_array($payslip->status, self::RELEASED, true);

        if (! $isOwnReleased) {
            $this->authorize('view-payroll');
        }

        $data = $payslip->load(['staff:id,name,designation', 'lines', 'claims', 'payRun:id,frequency,pay_date'])->toArray();

        // Under their own keys: the relations would serialise over the
        // l1_approved_by / approved_by id columns.
        $data['l1_approver'] = $payslip->l1ApprovedBy?->only(['id', 'name']);
        $data['approver'] = $payslip->approvedBy?->only(['id', 'name']);

        return response()->json(['status' => true, 'message' => 'Payslip', 'data' => $data]);
    }

    public function mine(Request $request)
    {
        $data = Payslip::where('user_id', $request->user()->id)
            ->whereIn('status', self::RELEASED)
            ->orderByDesc('period_start')
            ->get();

        return response()->json(['status' => true, 'message' => 'My payslips', 'data' => $data]);
    }

    public function regenerate(Request $request, PayslipGenerator $generator)
    {
        $this->authorize('manage-salaries');

        $ids = $this->validateIds($request);

        foreach ($ids as $id) {
            $generator->regenerate(Payslip::findOrFail($id));
        }

        return response()->json(['status' => true, 'message' => count($ids).' payslip(s) regenerated.']);
    }

    public function approve(Request $request)
    {
        $this->authorize('approve-salaries-l1');

        $result = $this->approvals->approve($this->validateIds($request), $request->user());

        return response()->json([
            'status' => true,
            'message' => count($result['approved']).' approved, '.count($result['l1_approved']).' approved at level 1, '.count($result['skipped']).' skipped.',
            'data' => $result,
        ]);
    }

    public function carryForward(Request $request)
    {
        $this->authorize('manage-salaries');

        $count = $this->approvals->carryForward($this->validateIds($request), $request->user());

        return response()->json(['status' => true, 'message' => "{$count} payslip(s) carried forward."]);
    }

    public function pendingCheques()
    {
        $this->authorize('view-payroll');

        $data = Payslip::with('staff:id,name')
            ->where('status', 'approved')
            ->where('payment_method', 'cheque')
            ->whereNull('cheque_number')
            ->orderBy('period_start')
            ->get(['id', 'user_id', 'period_start', 'period_end', 'currency', 'net_pay']);

        return response()->json(['status' => true, 'message' => 'Payslips awaiting a cheque number', 'data' => $data]);
    }

    public function saveCheque(Request $request)
    {
        $this->authorize('manage-salaries');

        $request->validate(['cheque_number' => ['required', 'string', 'max:255']]);

        $this->approvals->saveChequeNumber($this->find($request), $request->cheque_number);

        return response()->json(['status' => true, 'message' => 'Cheque number saved.']);
    }

    private function find(Request $request): Payslip
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('payslips', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        return Payslip::findOrFail($validated['id']);
    }

    private function validateIds(Request $request): array
    {
        return $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['integer', 'distinct', ValidationRule::exists('payslips', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ])['ids'];
    }
}
