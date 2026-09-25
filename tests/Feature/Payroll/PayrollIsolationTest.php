<?php

namespace Tests\Feature\Payroll;

use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Payroll\Models\PayRun;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Models\PayslipLine;
use App\Modules\Payroll\Models\SalaryAdjustment;
use App\Modules\Payroll\Models\SalaryClaim;
use App\Modules\Payroll\Models\StaffBankAccount;
use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Payroll\Models\StaffSalaryComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsTenantIsolation;
use Tests\TestCase;

class PayrollIsolationTest extends TestCase
{
    use AssertsTenantIsolation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    private function payslipB(): Payslip
    {
        $run = PayRun::create(['frequency' => 'monthly', 'period_start' => '2026-03-01', 'period_end' => '2026-03-31']);

        return Payslip::create([
            'pay_run_id' => $run->id, 'user_id' => $this->userB->id, 'period_start' => '2026-03-01', 'period_end' => '2026-03-31',
            'currency' => 'USD', 'period_days' => 31, 'payment_days' => 31,
            'gross_earnings' => 1, 'total_deductions' => 0, 'net_pay' => 1, 'generated_at' => now(),
        ]);
    }

    public function test_pay_components_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(PayComponent::class, fn () => PayComponent::create([
            'name' => 'B bonus', 'code' => 'B_BONUS', 'type' => 'earning', 'calculation' => 'fixed',
        ]));
    }

    public function test_staff_salaries_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(StaffSalary::class, fn () => StaffSalary::create([
            'user_id' => $this->userB->id, 'effective_from' => '2026-01-01', 'pay_frequency' => 'monthly', 'currency' => 'USD',
        ]));
    }

    public function test_staff_salary_components_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(StaffSalaryComponent::class, function () {
            $salary = StaffSalary::create(['user_id' => $this->userB->id, 'effective_from' => '2026-01-01', 'pay_frequency' => 'monthly', 'currency' => 'USD']);

            return $salary->components()->create(['pay_component_id' => PayComponent::basic()->id, 'value' => 1000]);
        });
    }

    public function test_bank_accounts_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(StaffBankAccount::class, fn () => StaffBankAccount::create([
            'user_id' => $this->userB->id, 'account_holder_name' => 'B', 'bank_name' => 'B', 'bank_country' => 'US', 'account_number' => '1',
        ]));
    }

    public function test_salary_adjustments_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(SalaryAdjustment::class, fn () => SalaryAdjustment::create([
            'user_id' => $this->userB->id, 'type' => 'credit', 'amount' => 1, 'title' => 'B', 'effective_date' => '2026-03-01',
        ]));
    }

    public function test_pay_runs_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(PayRun::class, fn () => PayRun::create([
            'frequency' => 'monthly', 'period_start' => '2026-03-01', 'period_end' => '2026-03-31',
        ]));
    }

    public function test_payslips_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(Payslip::class, fn () => $this->payslipB());
    }

    public function test_payslip_lines_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(PayslipLine::class, fn () => $this->payslipB()->lines()->create([
            'source' => 'component', 'label' => 'Basic', 'type' => 'earning', 'amount' => 1,
        ]));
    }

    public function test_salary_claims_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(SalaryClaim::class, fn () => SalaryClaim::create([
            'payslip_id' => $this->payslipB()->id, 'user_id' => $this->userB->id, 'reason' => 'B',
        ]));
    }
}
