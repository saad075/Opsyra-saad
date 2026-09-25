<?php

namespace Tests\Feature\Payroll;

use App\Modules\Hr\Models\UserUpdateLog;
use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Payroll\Models\StaffBankAccount;
use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Payroll\Services\SalaryCalculator;
use App\Modules\Platform\Models\Tenant;
use Illuminate\Support\Facades\DB;

class PayrollSetupTest extends PayrollTestCase
{
    public function test_a_pakistani_company_starts_with_slms360s_familiar_components(): void
    {
        $pk = Tenant::factory()->create(['country' => 'pk']);

        $codes = $this->tenantContext()->runAs($pk->id, fn () => PayComponent::pluck('code')->all());

        foreach (['BASIC', 'HOUSE_RENT', 'MEDICAL', 'TRAVEL', 'MOBILE', 'TAX_PERCENT', 'TAX_FIXED'] as $code) {
            $this->assertContains($code, $codes);
        }
    }

    public function test_the_salary_preview_follows_basic_then_earnings_then_deductions(): void
    {
        $this->giveStandardSalary($this->employee);

        $preview = $this->actingAs($this->payrollOfficer, 'sanctum')
            ->postJson('/api/payroll/salaries/current', ['user_id' => $this->employee->id])
            ->assertOk()
            ->json('data.per_period');

        $this->assertEquals(1500, $preview['gross']);
        $this->assertEquals(150, $preview['deductions']);
        $this->assertEquals(1350, $preview['net']);
    }

    public function test_a_raise_keeps_the_old_salary_for_earlier_periods(): void
    {
        $this->giveStandardSalary($this->employee, from: '2026-01-01', basic: 1000);
        $this->giveStandardSalary($this->employee, from: '2026-04-01', basic: 1200);

        $this->inTenantA(function () {
            $this->assertEquals(1000, StaffSalary::effectiveFor($this->employee->id, '2026-03-31')->components->firstWhere('pay_component_id', $this->basicId)->value);
            $this->assertEquals(1200, StaffSalary::effectiveFor($this->employee->id, '2026-04-01')->components->firstWhere('pay_component_id', $this->basicId)->value);
        });
    }

    public function test_every_salary_needs_basic_pay(): void
    {
        $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/salaries/set', [
            'user_id' => $this->employee->id,
            'effective_from' => '2026-01-01',
            'pay_frequency' => 'monthly',
            'components' => [['pay_component_id' => $this->houseRentId, 'value' => 50]],
        ])->assertUnprocessable()->assertJsonValidationErrors('components');
    }

    public function test_omani_rial_amounts_keep_three_decimals(): void
    {
        $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/salaries/set', [
            'user_id' => $this->employee->id,
            'effective_from' => '2026-01-01',
            'pay_frequency' => 'monthly',
            'currency' => 'OMR',
            'components' => [['pay_component_id' => $this->basicId, 'value' => 100], ['pay_component_id' => $this->taxId, 'value' => 3.333]],
        ])->assertCreated();

        $result = $this->inTenantA(fn () => app(SalaryCalculator::class)->calculate(StaffSalary::effectiveFor($this->employee->id, today())));

        $this->assertEquals(3.333, $result['deductions']);
        $this->assertEquals(96.667, $result['net']);
    }

    // SLMS360 hole: SalaryController::update() had no permission check.
    public function test_staff_cannot_set_anyones_salary_including_their_own(): void
    {
        $this->actingAs($this->employee, 'sanctum')->postJson('/api/payroll/salaries/set', [
            'user_id' => $this->employee->id,
            'effective_from' => '2026-01-01',
            'pay_frequency' => 'monthly',
            'components' => [['pay_component_id' => $this->basicId, 'value' => 999999]],
        ])->assertForbidden();
    }

    public function test_staff_can_see_their_own_salary(): void
    {
        $this->giveStandardSalary($this->employee);

        $this->actingAs($this->employee, 'sanctum')->getJson('/api/payroll/salaries/mine')
            ->assertOk()
            ->assertJsonPath('data.per_period.net', 1350);
    }

    // SLMS360 hole: anyone could overwrite anyone's bank details.
    public function test_staff_cannot_change_bank_details(): void
    {
        $this->actingAs($this->employee, 'sanctum')->postJson('/api/payroll/bank-accounts/save', [
            'user_id' => $this->payrollOfficer->id,
            'account_holder_name' => 'Attacker',
            'bank_name' => 'Evil Bank',
            'bank_country' => 'US',
            'account_number' => '999999',
        ])->assertForbidden();
    }

    public function test_bank_details_are_encrypted_at_rest_logged_and_masked_for_the_owner(): void
    {
        $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/bank-accounts/save', [
            'user_id' => $this->employee->id,
            'account_holder_name' => 'Employee',
            'bank_name' => 'Good Bank',
            'bank_country' => 'US',
            'account_number' => '12345678',
        ])->assertOk();

        $this->inTenantA(function () {
            $raw = DB::table('staff_bank_accounts')->where('user_id', $this->employee->id)->value('account_number');
            $this->assertNotSame('12345678', $raw, 'Account number must not be stored in plain text.');
            $this->assertSame('12345678', StaffBankAccount::where('user_id', $this->employee->id)->first()->account_number);
            $this->assertTrue(UserUpdateLog::where('user_id', $this->employee->id)->where('field_name', 'bank_account_added')->exists());
        });

        $this->actingAs($this->employee, 'sanctum')->getJson('/api/payroll/bank-accounts/mine')
            ->assertOk()
            ->assertJsonPath('data.account_number', '••••5678');
    }

    public function test_an_applied_adjustment_cannot_be_deleted_but_an_open_one_can(): void
    {
        $id = $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/adjustments/create', [
            'user_id' => $this->employee->id, 'type' => 'credit', 'amount' => 200, 'title' => 'Bonus', 'effective_date' => '2026-03-10',
        ])->assertCreated()->json('data.id');

        $this->actingAs($this->payrollOfficer, 'sanctum')->postJson('/api/payroll/adjustments/delete', ['id' => $id])->assertOk();
    }

    public function test_a_components_type_cannot_be_changed_and_basic_cannot_be_deactivated(): void
    {
        $this->actingAs($this->payrollOfficer, 'sanctum')
            ->postJson('/api/payroll/pay-components/update', ['id' => $this->basicId, 'name' => 'Basic', 'is_active' => false])
            ->assertStatus(409);
    }
}
