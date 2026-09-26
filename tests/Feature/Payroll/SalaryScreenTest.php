<?php

namespace Tests\Feature\Payroll;

use App\Modules\Payroll\Models\StaffBankAccount;
use App\Modules\Payroll\Models\StaffSalary;

/**
 * The Salaries screen's two reads: the overview of everyone, and the live
 * preview while editing a salary.
 */
class SalaryScreenTest extends PayrollTestCase
{
    public function test_the_overview_lists_everyone_to_be_paid_with_their_salary_and_bank_status(): void
    {
        $this->travelTo('2026-03-10');
        $this->giveStandardSalary($this->employee);
        $this->inTenantA(fn () => StaffBankAccount::create([
            'user_id' => $this->employee->id, 'account_holder_name' => 'E', 'bank_name' => 'B', 'bank_country' => 'US', 'account_number' => '1234',
        ]));
        $leaver = $this->inTenantA(fn () => $this->makeStaff(['status' => 'offboarded']));

        $rows = collect($this->actingAs($this->payrollOfficer, 'sanctum')->getJson('/api/payroll/salaries/overview')->assertOk()->json('data'))->keyBy('id');

        $this->assertSame(1500, (int) $rows[$this->employee->id]['salary']['gross']);
        $this->assertSame(1350, (int) $rows[$this->employee->id]['salary']['net']);
        $this->assertSame('monthly', $rows[$this->employee->id]['salary']['pay_frequency']);
        $this->assertTrue($rows[$this->employee->id]['has_bank_account']);

        $this->assertNull($rows[$this->approverL1->id]['salary']);
        $this->assertFalse($rows[$this->approverL1->id]['has_bank_account']);
        $this->assertFalse($rows->has($leaver->id), 'Leavers are not listed.');
    }

    public function test_someone_starting_later_shows_the_salary_they_will_start_on(): void
    {
        $this->travelTo('2026-03-10');
        $this->giveStandardSalary($this->employee, from: '2026-04-01');

        $row = collect($this->actingAs($this->payrollOfficer, 'sanctum')->getJson('/api/payroll/salaries/overview')->json('data'))->firstWhere('id', $this->employee->id);

        $this->assertSame('2026-04-01', substr($row['salary']['effective_from'], 0, 10));
    }

    public function test_the_preview_matches_the_payslip_calculation_and_saves_nothing(): void
    {
        $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/salaries/preview', [
            'currency' => 'USD',
            'components' => [
                ['pay_component_id' => $this->basicId, 'value' => 1000],
                ['pay_component_id' => $this->houseRentId, 'value' => 50],
                ['pay_component_id' => $this->taxId, 'value' => 10],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.gross', 1500)
            ->assertJsonPath('data.deductions', 150)
            ->assertJsonPath('data.net', 1350)
            ->assertJsonCount(3, 'data.lines');

        $this->assertSame(0, $this->inTenantA(fn () => StaffSalary::count()));
    }

    public function test_the_preview_applies_the_same_rules_and_permission_as_saving(): void
    {
        $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/salaries/preview', [
            'components' => [['pay_component_id' => $this->houseRentId, 'value' => 50]],
        ])->assertUnprocessable()->assertJsonValidationErrors('components');

        $this->actingAs($this->employee, 'sanctum')->getJson('/api/payroll/salaries/overview')->assertForbidden();
        $this->actingAs($this->employee, 'sanctum')->postJson('/api/payroll/salaries/preview', [
            'components' => [['pay_component_id' => $this->basicId, 'value' => 1]],
        ])->assertForbidden();
    }
}
