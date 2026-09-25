<?php

namespace Tests\Feature\Payroll;

use App\Models\User;
use App\Modules\Payroll\Models\PayComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

/**
 * Shared fixture: tenant A has a payroll officer (salary management +
 * adjustments), an L1 and an L2 approver, and one employee; plus a
 * percent-of-basic earning and a percent-of-gross deduction alongside the
 * seeded Basic, so the calculation order is exercised.
 */
abstract class PayrollTestCase extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected User $payrollOfficer;

    protected User $approverL1;

    protected User $approverL2;

    protected User $employee;

    protected int $basicId;

    protected int $houseRentId;

    protected int $taxId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        foreach (['HRMS-SalaryManagement', 'HRMS-StaffAdjustments', 'HRMS-Approval-L1', 'HRMS-Approval-L2'] as $name) {
            Permission::findOrCreate($name, 'sanctum');
        }

        $this->inTenantA(function () {
            $this->payrollOfficer = $this->makeStaff();
            $this->payrollOfficer->givePermissionTo(['HRMS-SalaryManagement', 'HRMS-StaffAdjustments']);

            $this->approverL1 = $this->makeStaff();
            $this->approverL1->givePermissionTo('HRMS-Approval-L1');

            $this->approverL2 = $this->makeStaff();
            $this->approverL2->givePermissionTo('HRMS-Approval-L2');

            $this->employee = $this->makeStaff(['joined_at' => '2026-01-01']);

            $this->basicId = PayComponent::basic()->id;
            $this->houseRentId = PayComponent::create(['name' => 'House rent', 'code' => 'HOUSE_RENT', 'type' => 'earning', 'calculation' => 'percent_of_basic', 'sequence' => 10])->id;
            $this->taxId = PayComponent::create(['name' => 'Income tax', 'code' => 'TAX', 'type' => 'deduction', 'calculation' => 'percent_of_gross', 'sequence' => 80])->id;
        });
    }

    protected function inTenantA(\Closure $callback): mixed
    {
        return $this->tenantContext()->runAs($this->tenantA->id, $callback);
    }

    protected function makeStaff(array $attributes = []): User
    {
        return User::factory()->for($this->tenantA)->create($attributes + ['status' => 'active']);
    }

    /**
     * Basic 1000 + house rent 50% of basic, tax 10% of gross → per full
     * period: gross 1500, tax 150, net 1350.
     */
    protected function giveStandardSalary(User $staff, string $frequency = 'monthly', string $from = '2026-01-01', float $basic = 1000): void
    {
        $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/salaries/set', [
            'user_id' => $staff->id,
            'effective_from' => $from,
            'pay_frequency' => $frequency,
            'currency' => 'USD',
            'components' => [
                ['pay_component_id' => $this->basicId, 'value' => $basic],
                ['pay_component_id' => $this->houseRentId, 'value' => 50],
                ['pay_component_id' => $this->taxId, 'value' => 10],
            ],
        ])->assertCreated();
    }

    protected function startRun(string $frequency = 'monthly', string $periodStart = '2026-03-01'): int
    {
        return $this->actingAs($this->payrollOfficer, 'sanctum')
            ->postJson('/api/payroll/pay-runs/create', ['frequency' => $frequency, 'period_start' => $periodStart])
            ->assertCreated()
            ->json('data.id');
    }
}
