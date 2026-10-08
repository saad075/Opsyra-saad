<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Payroll\Models\PayRun;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Models\PayslipLine;
use App\Modules\Payroll\Models\SalaryClaim;
use App\Modules\Payroll\Models\StaffBankAccount;
use App\Modules\Payroll\Models\StaffSalary;
use Illuminate\Http\Request;

/**
 * The Payroll module's dashboard. Money is always grouped by currency —
 * never summed across currencies.
 */
class PayrollDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('view-payroll');

        $user = $request->user();

        $runs = PayRun::orderByDesc('period_start')->limit(6)->get();

        $runSummaries = $runs->map(function (PayRun $run) {
            $slips = Payslip::where('pay_run_id', $run->id)->get(['currency', 'gross_earnings', 'total_deductions', 'net_pay', 'status']);

            return [
                'id' => $run->id,
                'frequency' => $run->frequency,
                'period_start' => $run->period_start->toDateString(),
                'period_end' => $run->period_end->toDateString(),
                'status' => $run->status,
                'payslips' => $slips->count(),
                'approved' => $slips->whereIn('status', ['approved', 'carried_forward'])->count(),
                'failures' => count($run->errors ?? []),
                'totals' => $slips->groupBy('currency')->map(fn ($group, $currency) => [
                    'currency' => $currency,
                    'gross' => round($group->sum('gross_earnings'), 3),
                    'deductions' => round($group->sum('total_deductions'), 3),
                    'net' => round($group->sum('net_pay'), 3),
                ])->values(),
            ];
        });

        $latest = $runs->first();

        $activeStaffIds = User::where('status', 'active')->pluck('id');
        $withSalary = $activeStaffIds->filter(fn ($id) => StaffSalary::effectiveFor($id, today()) !== null);
        $withBank = StaffBankAccount::whereIn('user_id', $activeStaffIds)->pluck('user_id');

        return response()->json([
            'status' => true,
            'message' => 'Payroll dashboard',
            'data' => [
                'awaiting' => [
                    'drafts' => Payslip::where('status', 'draft')->count(),
                    'level_2' => Payslip::where('status', 'l1_approved')->count(),
                    // What this user can act on right now.
                    'for_me' => $user->can('approve-salaries-l2')
                        ? Payslip::whereIn('status', ['draft', 'l1_approved'])->where(fn ($q) => $q->whereNull('l1_approved_by')->orWhere('l1_approved_by', '!=', $user->id))->count()
                        : ($user->can('approve-salaries-l1') ? Payslip::where('status', 'draft')->count() : 0),
                    'cheques' => Payslip::where('status', 'approved')->where('payment_method', 'cheque')->whereNull('cheque_number')->count(),
                    'claims' => SalaryClaim::where('status', 'open')->count(),
                ],

                'coverage' => [
                    'active_staff' => $activeStaffIds->count(),
                    'with_salary' => $withSalary->count(),
                    'with_bank_details' => $withBank->count(),
                ],

                // Oldest first, for the trend chart.
                'runs' => $runSummaries->reverse()->values(),

                'latest_breakdown' => $latest
                    ? PayslipLine::whereHas('payslip', fn ($q) => $q->where('pay_run_id', $latest->id))
                        ->get(['label', 'type', 'amount'])
                        ->groupBy(fn ($line) => $line->type.'|'.$line->label)
                        ->map(fn ($group) => ['label' => $group->first()->label, 'type' => $group->first()->type, 'amount' => round($group->sum('amount'), 3)])
                        ->sortByDesc('amount')->values()
                    : [],
            ],
        ]);
    }
}
