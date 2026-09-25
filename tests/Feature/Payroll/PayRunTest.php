<?php

namespace Tests\Feature\Payroll;

use App\Modules\Hr\Models\StaffOnboardingRequest;
use App\Modules\Payroll\Models\PayRun;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Models\SalaryAdjustment;
use App\Modules\Payroll\Models\StaffBankAccount;

class PayRunTest extends PayrollTestCase
{
    protected function payslipFor(int $userId, ?int $runId = null): ?Payslip
    {
        return $this->inTenantA(fn () => Payslip::with('lines')->where('user_id', $userId)
            ->when($runId, fn ($q) => $q->where('pay_run_id', $runId))
            ->latest('id')->first());
    }

    public function test_a_monthly_run_produces_itemised_payslips_for_salaried_staff_only(): void
    {
        $this->giveStandardSalary($this->employee);

        $runId = $this->startRun();

        $payslip = $this->payslipFor($this->employee->id, $runId);
        $this->assertNotNull($payslip);
        $this->assertSame('draft', $payslip->status);
        $this->assertEquals(1500, $payslip->gross_earnings);
        $this->assertEquals(150, $payslip->total_deductions);
        $this->assertEquals(1350, $payslip->net_pay);
        $this->assertEqualsCanonicalizing(['BASIC', 'HOUSE_RENT', 'TAX'], $payslip->lines->pluck('code')->all());

        $this->assertNull($this->payslipFor($this->payrollOfficer->id), 'No salary set up, no payslip.');
        $this->inTenantA(fn () => $this->assertSame('completed', PayRun::findOrFail($runId)->status));
    }

    public function test_someone_joining_mid_month_is_paid_for_the_days_employed(): void
    {
        $joiner = $this->inTenantA(fn () => $this->makeStaff(['joined_at' => '2026-03-17']));
        $this->giveStandardSalary($joiner);

        $this->startRun();

        // 15 of March's 31 days: basic 1000 × 15/31 = 483.87; house rent 50% = 241.94;
        // gross 725.81; tax 10% = 72.58; net 653.23.
        $payslip = $this->payslipFor($joiner->id);
        $this->assertSame(15, $payslip->payment_days);
        $this->assertEquals(725.81, $payslip->gross_earnings);
        $this->assertEquals(653.23, $payslip->net_pay);
    }

    public function test_someone_awaiting_onboarding_approval_is_not_paid(): void
    {
        $candidate = $this->inTenantA(function () {
            $candidate = $this->makeStaff(['status' => 'pending_approval', 'joined_at' => '2026-03-01']);
            StaffOnboardingRequest::create(['user_id' => $candidate->id, 'status' => 'pending']);

            return $candidate;
        });
        $this->giveStandardSalary($candidate);

        $this->startRun();

        $this->assertNull($this->payslipFor($candidate->id));
    }

    public function test_adjustments_are_applied_once_and_only_on_the_run_that_covers_them(): void
    {
        $this->giveStandardSalary($this->employee);

        foreach ([['credit', 200, 'Bonus', '2026-03-10'], ['debit', 50, 'Advance recovery', '2026-03-20'], ['credit', 999, 'April bonus', '2026-04-05']] as [$type, $amount, $title, $date]) {
            $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/adjustments/create', [
                'user_id' => $this->employee->id, 'type' => $type, 'amount' => $amount, 'title' => $title, 'effective_date' => $date,
            ])->assertCreated();
        }

        $this->startRun('monthly', '2026-03-01');

        $march = $this->payslipFor($this->employee->id);
        $this->assertEquals(1350 + 200 - 50, $march->net_pay, 'Tax is on salary gross only; adjustments apply after, as SLMS360.');

        // Regenerating the draft must not double-count the adjustments.
        $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/payslips/regenerate', ['ids' => [$march->id]])->assertOk();
        $this->assertEquals(1500, $this->payslipFor($this->employee->id)->net_pay);

        $this->inTenantA(fn () => $this->assertNull(SalaryAdjustment::where('title', 'April bonus')->value('payslip_id')));
    }

    public function test_biweekly_runs_cover_fourteen_days_and_cannot_overlap(): void
    {
        $this->giveStandardSalary($this->employee, 'biweekly');

        $runId = $this->startRun('biweekly', '2026-03-02');
        $this->inTenantA(fn () => $this->assertSame('2026-03-15', PayRun::findOrFail($runId)->period_end->toDateString()));
        $this->assertEquals(1350, $this->payslipFor($this->employee->id)->net_pay, 'Salary amounts are per pay period.');

        $this->actingAs($this->payrollOfficer, 'sanctum')
            ->postJson('/api/payroll/pay-runs/create', ['frequency' => 'biweekly', 'period_start' => '2026-03-09'])
            ->assertUnprocessable();
    }

    public function test_a_monthly_run_must_start_on_the_first(): void
    {
        $this->actingAs($this->payrollOfficer, 'sanctum')
            ->postJson('/api/payroll/pay-runs/create', ['frequency' => 'monthly', 'period_start' => '2026-03-05'])
            ->assertUnprocessable();
    }

    // SLMS360 hole: postSalary() had no permission check.
    public function test_staff_cannot_start_a_pay_run(): void
    {
        $this->actingAs($this->employee, 'sanctum')
            ->postJson('/api/payroll/pay-runs/create', ['frequency' => 'monthly', 'period_start' => '2026-03-01'])
            ->assertForbidden();
    }

    public function test_approval_goes_l1_then_l2_and_sets_the_payment_method(): void
    {
        $this->giveStandardSalary($this->employee);
        $this->startRun();
        $id = $this->payslipFor($this->employee->id)->id;

        $this->inTenantA(fn () => StaffBankAccount::create([
            'user_id' => $this->employee->id, 'account_holder_name' => 'E', 'bank_name' => 'B', 'bank_country' => 'US', 'account_number' => '1234',
        ]));

        $this->actingAs($this->approverL1, 'sanctum')->postJson('/api/payroll/payslips/approve', ['ids' => [$id]])
            ->assertOk()->assertJsonPath('data.l1_approved', [$id]);

        // L1 again can't push it further.
        $this->actingAs($this->approverL1, 'sanctum')->postJson('/api/payroll/payslips/approve', ['ids' => [$id]])
            ->assertOk()->assertJsonCount(1, 'data.skipped');

        $this->actingAs($this->approverL2, 'sanctum')->postJson('/api/payroll/payslips/approve', ['ids' => [$id]])
            ->assertOk()->assertJsonPath('data.approved', [$id]);

        $payslip = $this->payslipFor($this->employee->id);
        $this->assertSame('approved', $payslip->status);
        $this->assertSame('transfer', $payslip->payment_method);

        // SLMS360 hole: an L1 approver could re-approve an L2-approved slip back down.
        $this->actingAs($this->approverL1, 'sanctum')->postJson('/api/payroll/payslips/approve', ['ids' => [$id]]);
        $this->assertSame('approved', $this->payslipFor($this->employee->id)->status);
    }

    public function test_the_same_person_cannot_approve_at_both_levels(): void
    {
        $both = $this->inTenantA(function () {
            $user = $this->makeStaff();
            $user->givePermissionTo('HRMS-Approval-L2');

            return $user;
        });

        $this->giveStandardSalary($this->employee);
        $this->startRun();
        $id = $this->payslipFor($this->employee->id)->id;

        $this->inTenantA(fn () => Payslip::whereKey($id)->update(['status' => 'l1_approved', 'l1_approved_by' => $both->id]));

        $this->actingAs($both, 'sanctum')->postJson('/api/payroll/payslips/approve', ['ids' => [$id]])
            ->assertOk()->assertJsonCount(1, 'data.skipped');
    }

    public function test_no_bank_details_means_cheque_and_a_cheque_number_is_captured_afterwards(): void
    {
        $this->giveStandardSalary($this->employee);
        $this->startRun();
        $id = $this->payslipFor($this->employee->id)->id;

        $this->actingAs($this->approverL2, 'sanctum')->postJson('/api/payroll/payslips/approve', ['ids' => [$id]])->assertOk();

        $this->actingAs($this->payrollOfficer, 'sanctum')->getJson('/api/payroll/payslips/pending-cheques')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($this->payrollOfficer, 'sanctum')
            ->postJson('/api/payroll/payslips/cheque-number', ['id' => $id, 'cheque_number' => 'CHQ-001'])
            ->assertOk();

        $this->assertSame('CHQ-001', $this->payslipFor($this->employee->id)->cheque_number);
    }

    public function test_approved_payslips_cannot_be_regenerated(): void
    {
        $this->giveStandardSalary($this->employee);
        $this->startRun();
        $id = $this->payslipFor($this->employee->id)->id;

        $this->actingAs($this->approverL2, 'sanctum')->postJson('/api/payroll/payslips/approve', ['ids' => [$id]])->assertOk();

        $this->actingAs($this->payrollOfficer, 'sanctum')
            ->postJson('/api/payroll/payslips/regenerate', ['ids' => [$id]])
            ->assertStatus(409);
    }

    public function test_carry_forward_moves_the_pay_into_next_months_payslip(): void
    {
        $this->giveStandardSalary($this->employee);
        $this->startRun('monthly', '2026-03-01');
        $march = $this->payslipFor($this->employee->id);

        $this->actingAs($this->payrollOfficer, 'sanctum')
            ->postJson('/api/payroll/payslips/carry-forward', ['ids' => [$march->id]])
            ->assertOk();

        $this->startRun('monthly', '2026-04-01');

        $april = $this->payslipFor($this->employee->id);
        $this->assertEquals(1350 * 2, $april->net_pay);
        $this->assertSame('carried_forward', $this->inTenantA(fn () => Payslip::findOrFail($march->id)->status));
    }

    public function test_staff_see_only_their_own_released_payslips(): void
    {
        $this->giveStandardSalary($this->employee);
        $this->startRun();
        $id = $this->payslipFor($this->employee->id)->id;

        $this->actingAs($this->employee, 'sanctum')->postJson('/api/payroll/payslips/show', ['id' => $id])->assertForbidden();
        $this->actingAs($this->employee, 'sanctum')->getJson('/api/payroll/payslips/mine')->assertJsonCount(0, 'data');

        $this->actingAs($this->approverL2, 'sanctum')->postJson('/api/payroll/payslips/approve', ['ids' => [$id]])->assertOk();

        $this->actingAs($this->employee, 'sanctum')->postJson('/api/payroll/payslips/show', ['id' => $id])->assertOk()->assertJsonCount(3, 'data.lines');

        $colleague = $this->inTenantA(fn () => $this->makeStaff());
        $this->actingAs($colleague, 'sanctum')->postJson('/api/payroll/payslips/show', ['id' => $id])->assertForbidden();
    }

    public function test_a_salary_query_can_be_settled_with_an_adjustment_on_the_next_payslip(): void
    {
        // The settlement is dated "today", so today must fall before April's run.
        $this->travelTo('2026-04-10');

        $this->giveStandardSalary($this->employee);
        $this->startRun('monthly', '2026-03-01');
        $id = $this->payslipFor($this->employee->id)->id;
        $this->actingAs($this->approverL2, 'sanctum')->postJson('/api/payroll/payslips/approve', ['ids' => [$id]])->assertOk();

        $claimId = $this->actingAs($this->employee, 'sanctum')
            ->postJson('/api/payroll/claims/create', ['payslip_id' => $id, 'reason' => 'Overtime missing'])
            ->assertCreated()->json('data.id');

        $this->actingAs($this->employee, 'sanctum')
            ->postJson('/api/payroll/claims/create', ['payslip_id' => $id, 'reason' => 'Again'])
            ->assertStatus(409);

        $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/claims/approve', [
            'id' => $claimId, 'response' => 'Agreed', 'adjustment_type' => 'credit', 'adjustment_amount' => 75,
        ])->assertOk();

        $this->startRun('monthly', '2026-04-01');
        $this->assertEquals(1350 + 75, $this->payslipFor($this->employee->id)->net_pay);
    }

    public function test_another_tenant_cannot_approve_or_read_these_payslips(): void
    {
        $this->giveStandardSalary($this->employee);
        $this->startRun();
        $id = $this->payslipFor($this->employee->id)->id;

        $this->tenantContext()->runAs($this->tenantB->id, fn () => $this->userB->givePermissionTo(['HRMS-Approval-L2', 'HRMS-SalaryManagement']));

        $this->actingAs($this->userB, 'sanctum')->postJson('/api/payroll/payslips/approve', ['ids' => [$id]])->assertUnprocessable();
        $this->actingAs($this->userB, 'sanctum')->postJson('/api/payroll/payslips/show', ['id' => $id])->assertUnprocessable();
        $this->actingAs($this->userB, 'sanctum')->postJson('/api/payroll/payslips/list', [])->assertOk()->assertJsonCount(0, 'data');
    }
}
