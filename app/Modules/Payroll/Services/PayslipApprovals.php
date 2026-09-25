<?php

namespace App\Modules\Payroll\Services;

use App\Models\User;
use App\Modules\Payroll\Exceptions\PayrollException;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Models\SalaryAdjustment;
use App\Modules\Payroll\Models\StaffBankAccount;
use Illuminate\Support\Facades\DB;

/**
 * Adapted from SLMS360's SalaryController::approve()/carryForward()/
 * saveCheckNumber(). Two levels, as SLMS360: draft → L1 approved → approved.
 * An L2 holder may approve a draft outright (they ARE the higher approval —
 * same rule as the onboarding gate). Fixed while porting: SLMS360 let an L1
 * holder re-approve an L2-approved payslip back down to L1, and let the
 * same person act at both levels; neither is possible here.
 */
class PayslipApprovals
{
    /**
     * @param  list<int>  $ids
     * @return array{l1_approved: list<int>, approved: list<int>, skipped: list<array{id: int, reason: string}>}
     */
    public function approve(array $ids, User $actor): array
    {
        $result = ['l1_approved' => [], 'approved' => [], 'skipped' => []];
        $isL2 = $actor->can('approve-salaries-l2');

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $actor, $isL2, &$result) {
                $payslip = Payslip::whereKey($id)->lockForUpdate()->firstOrFail();

                // A full closure, not fn(): arrow functions capture by value,
                // so the skipped list would silently stay empty.
                $skip = function (string $reason) use ($payslip, &$result) {
                    $result['skipped'][] = ['id' => $payslip->id, 'reason' => $reason];
                };

                if ($payslip->status === 'draft' && ! $isL2) {
                    $payslip->update(['status' => 'l1_approved', 'l1_approved_by' => $actor->id, 'l1_approved_at' => now()]);
                    $result['l1_approved'][] = $payslip->id;
                } elseif ($payslip->status === 'draft' || $payslip->status === 'l1_approved') {
                    if (! $isL2) {
                        $skip('Already approved at level 1; needs a level 2 approver.');
                    } elseif ($payslip->l1_approved_by === $actor->id) {
                        $skip('The same person cannot approve at both levels.');
                    } else {
                        $this->finalApprove($payslip, $actor);
                        $result['approved'][] = $payslip->id;
                    }
                } else {
                    $skip("Payslip is {$payslip->status}.");
                }
            });
        }

        return $result;
    }

    /**
     * Moves unpaid pay into the next period as a credit adjustment (SLMS360's
     * carry-forward). The payslip is closed as carried_forward.
     *
     * @param  list<int>  $ids
     */
    public function carryForward(array $ids, User $actor): int
    {
        $count = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $actor, &$count) {
                $payslip = Payslip::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($payslip->status === 'carried_forward') {
                    return;
                }

                if ($payslip->net_pay > 0) {
                    SalaryAdjustment::create([
                        'user_id' => $payslip->user_id,
                        'type' => 'credit',
                        'amount' => $payslip->net_pay,
                        'title' => 'Carried forward salary',
                        'label' => 'salary_carry_forward',
                        'description' => "From payslip #{$payslip->id} ({$payslip->period_start->toDateString()} to {$payslip->period_end->toDateString()}).",
                        'effective_date' => $payslip->period_end->copy()->addDay(),
                        'created_by' => $actor->id,
                    ]);
                }

                $payslip->update(['status' => 'carried_forward']);
                $count++;
            });
        }

        return $count;
    }

    public function saveChequeNumber(Payslip $payslip, string $chequeNumber): void
    {
        if ($payslip->status !== 'approved' || $payslip->payment_method !== 'cheque') {
            throw new PayrollException('Only an approved payslip paid by cheque takes a cheque number.');
        }

        $payslip->update(['cheque_number' => $chequeNumber]);
    }

    /**
     * Payment method is fixed at final approval, as SLMS360: transfer if bank
     * details are on file, otherwise a cheque number is captured afterwards
     * (so a missing cheque number never blocks bulk approval).
     */
    private function finalApprove(Payslip $payslip, User $actor): void
    {
        $account = StaffBankAccount::where('user_id', $payslip->user_id)->first();

        $payslip->update([
            'status' => 'approved',
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'payment_method' => $account?->canReceiveTransfer() ? 'transfer' : 'cheque',
        ]);
    }
}
