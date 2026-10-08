<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Payroll\Jobs\GeneratePayslips;
use App\Modules\Payroll\Models\PayRun;
use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Payroll\Support\PayPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Adapted from SLMS360's SalaryController::postSalary()/postSalaryBiweekly()/
 * manualSalary(), which had no permission check — any logged-in user could
 * start a pay run. Generation runs as a queued job; generate() re-runs it
 * for people added later or whose earlier attempt failed (SLMS360's
 * "manual salary" for one person).
 */
class PayRunController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-payroll');

        $data = PayRun::with('createdBy:id,name')
            ->withCount(self::statusCounts())
            ->when($request->filled('frequency'), fn ($q) => $q->where('frequency', $request->frequency))
            ->orderByDesc('period_start')
            ->limit(100)
            ->get();

        return response()->json(['status' => true, 'message' => 'Pay runs', 'data' => $data]);
    }

    public function show(Request $request)
    {
        $this->authorize('view-payroll');

        $validated = $request->validate([
            'id' => ['required', 'integer', ValidationRule::exists('pay_runs', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $run = PayRun::with('createdBy:id,name')->withCount(self::statusCounts())->findOrFail($validated['id']);

        // Totals per currency — two currencies are never added together.
        $totals = $run->payslips()
            ->toBase()
            ->selectRaw('currency, count(*) as payslips, sum(gross_earnings) as gross, sum(total_deductions) as deductions, sum(net_pay) as net')
            ->groupBy('currency')
            ->get()
            ->map(fn ($row) => [
                'currency' => $row->currency,
                'payslips' => (int) $row->payslips,
                'gross' => (float) $row->gross,
                'deductions' => (float) $row->deductions,
                'net' => (float) $row->net,
            ]);

        // Name the people whose payslip couldn't be generated.
        $errors = collect($run->errors ?? []);
        $names = User::whereIn('id', $errors->pluck('user_id')->filter())->pluck('name', 'id');

        return response()->json([
            'status' => true,
            'message' => 'Pay run',
            'data' => $run->toArray() + [
                'totals' => $totals,
                'failures' => $errors->map(fn ($e) => $e + ['name' => $names[$e['user_id'] ?? 0] ?? null])->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-salaries');

        $validated = $request->validate([
            'frequency' => ['required', ValidationRule::in(StaffSalary::FREQUENCIES)],
            'period_start' => ['required', 'date'],
            'pay_date' => ['nullable', 'date'],
        ]);

        try {
            $period = PayPeriod::for($validated['frequency'], $validated['period_start']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['period_start' => $e->getMessage()]);
        }

        // Two overlapping runs of the same frequency would pay the same days
        // twice — e.g. biweekly runs started on the 1st and the 8th.
        $overlapping = PayRun::where('frequency', $validated['frequency'])
            ->whereDate('period_start', '<=', $period->end)
            ->whereDate('period_end', '>=', $period->start)
            ->exists();

        if ($overlapping) {
            throw ValidationException::withMessages(['period_start' => 'A pay run of this frequency already covers some of these dates.']);
        }

        $run = PayRun::create([
            'frequency' => $validated['frequency'],
            'period_start' => $period->start,
            'period_end' => $period->end,
            'pay_date' => $validated['pay_date'] ?? null,
            'status' => 'processing',
            'created_by' => $request->user()->id,
        ]);

        GeneratePayslips::dispatch($run->id);

        return response()->json([
            'status' => true,
            'message' => 'Pay run started. Payslips are being generated.',
            'data' => $run->fresh(),
        ], 201);
    }

    public function generate(Request $request)
    {
        $this->authorize('manage-salaries');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'pay_run_id' => ['required', 'integer', ValidationRule::exists('pay_runs', 'id')->where('tenant_id', $tenantId)],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', ValidationRule::exists('users', 'id')->where('tenant_id', $tenantId)],
        ]);

        GeneratePayslips::dispatch($validated['pay_run_id'], $validated['user_ids'] ?? null);

        return response()->json([
            'status' => true,
            'message' => 'Generating any missing payslips for this run.',
            'data' => PayRun::findOrFail($validated['pay_run_id']),
        ]);
    }

    /** Payslips per approval stage, so a list shows where each run has got to. */
    private static function statusCounts(): array
    {
        $counts = [];

        foreach (['draft', 'l1_approved', 'approved', 'carried_forward'] as $status) {
            $counts["payslips as {$status}_count"] = fn ($q) => $q->where('status', $status);
        }

        return $counts;
    }
}
