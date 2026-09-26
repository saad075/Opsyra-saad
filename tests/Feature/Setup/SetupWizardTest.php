<?php

namespace Tests\Feature\Setup;

use App\Models\User;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\UserBranch;
use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Payroll\Models\StaffSalary;
use App\Modules\Setup\Models\SetupStepState;
use App\Modules\Setup\Models\TenantSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\AssertsTenantIsolation;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use AssertsTenantIsolation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        foreach (['HRMS-CompanySettings', 'HRMS-StaffManagement', 'HRMS-SalaryManagement', 'HRMS-Approval-L2'] as $name) {
            Permission::findOrCreate($name, 'sanctum');
        }

        $this->inTenantA(fn () => $this->userA->givePermissionTo(['HRMS-CompanySettings', 'HRMS-StaffManagement', 'HRMS-SalaryManagement']));
    }

    protected function inTenantA(\Closure $callback): mixed
    {
        return $this->tenantContext()->runAs($this->tenantA->id, $callback);
    }

    protected function wizardStatus(?User $as = null): array
    {
        return $this->actingAs($as ?? $this->userA, 'sanctum')->getJson('/api/setup-wizard/status')->assertOk()->json('data');
    }

    protected function step(array $status, string $key): array
    {
        return collect($status['steps'])->firstWhere('key', $key);
    }

    public function test_a_new_company_sees_every_modules_steps_starting_with_company_details(): void
    {
        $status = $this->wizardStatus();

        $keys = collect($status['steps'])->pluck('key')->all();
        foreach (['company_details', 'branches', 'departments', 'roles', 'approvers', 'staff', 'departure_reasons', 'checklist_templates', 'pay_components', 'salaries', 'bank_accounts'] as $key) {
            $this->assertContains($key, $keys);
        }

        $this->assertSame('company_details', $status['next_step']);
        $this->assertFalse($status['is_complete']);

        // Only Roles is already done: every tenant starts with default roles.
        $this->assertSame(['roles'], collect($status['steps'])->where('status', 'done')->pluck('key')->all());
    }

    public function test_steps_complete_from_the_data_itself(): void
    {
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/company-settings/update', [
            'company_name' => 'Acme Ltd', 'address' => '1 High Street', 'timezone' => 'Europe/London', 'currency' => 'gbp',
        ])->assertOk();

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/branches/create', [
            'name' => 'Head office', 'timezone' => 'Europe/London',
        ])->assertCreated();

        $status = $this->wizardStatus();
        $this->assertSame('done', $this->step($status, 'company_details')['status']);
        $this->assertSame('done', $this->step($status, 'branches')['status']);
        $this->assertSame('departments', $status['next_step']);

        $this->inTenantA(fn () => $this->assertSame('GBP', TenantSetting::get('currency')));
    }

    public function test_review_steps_are_done_when_confirmed_and_can_be_reopened(): void
    {
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/setup-wizard/mark-reviewed', ['key' => 'pay_components'])->assertOk();
        $this->assertSame('done', $this->step($this->wizardStatus(), 'pay_components')['status']);

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/setup-wizard/reset', ['key' => 'pay_components'])->assertOk();
        $this->assertSame('pending', $this->step($this->wizardStatus(), 'pay_components')['status']);
    }

    public function test_a_data_step_cannot_be_ticked_off_by_hand(): void
    {
        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/setup-wizard/mark-reviewed', ['key' => 'branches'])
            ->assertUnprocessable();
    }

    public function test_a_skipped_step_moves_the_wizard_on_but_setup_is_not_complete(): void
    {
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/setup-wizard/skip', ['key' => 'company_details'])->assertOk();

        $status = $this->wizardStatus();
        $this->assertSame('skipped', $this->step($status, 'company_details')['status']);
        $this->assertSame('branches', $status['next_step']);
        $this->assertFalse($status['is_complete']);
    }

    public function test_the_salaries_step_counts_active_staff_with_a_salary(): void
    {
        $this->inTenantA(function () {
            User::query()->update(['status' => 'active']);
            $colleague = User::factory()->for($this->tenantA)->create(['status' => 'active']);

            $salary = StaffSalary::create(['user_id' => $colleague->id, 'effective_from' => '2026-01-01', 'pay_frequency' => 'monthly', 'currency' => 'USD']);
            $salary->components()->create(['pay_component_id' => PayComponent::basic()->id, 'value' => 1000]);
        });

        $salaries = $this->step($this->wizardStatus(), 'salaries');
        $this->assertSame('pending', $salaries['status']);
        $this->assertSame('1 of 2 active staff have a salary', $salaries['detail']);
    }

    public function test_the_approvers_step_needs_a_level_2_approver(): void
    {
        $this->assertSame('pending', $this->step($this->wizardStatus(), 'approvers')['status']);

        $this->inTenantA(fn () => User::factory()->for($this->tenantA)->create()->givePermissionTo('HRMS-Approval-L2'));

        $this->assertSame('done', $this->step($this->wizardStatus(), 'approvers')['status']);
    }

    public function test_only_setup_admins_see_the_wizard_or_edit_company_details(): void
    {
        $staff = $this->inTenantA(fn () => User::factory()->for($this->tenantA)->create());

        $this->actingAs($staff, 'sanctum')->getJson('/api/setup-wizard/status')->assertForbidden();
        $this->actingAs($staff, 'sanctum')->postJson('/api/company-settings/update', [
            'company_name' => 'Hijacked', 'address' => 'x', 'timezone' => 'UTC',
        ])->assertForbidden();
    }

    public function test_one_companys_progress_and_settings_never_show_for_another(): void
    {
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/company-settings/update', [
            'company_name' => 'Acme Ltd', 'address' => '1 High Street', 'timezone' => 'Europe/London',
        ])->assertOk();

        $this->tenantContext()->runAs($this->tenantB->id, fn () => $this->userB->givePermissionTo('HRMS-CompanySettings'));

        $this->assertSame('pending', $this->step($this->wizardStatus($this->userB), 'company_details')['status']);
        $this->actingAs($this->userB, 'sanctum')->getJson('/api/company-settings/show')
            ->assertOk()
            ->assertJsonPath('data.company_name', $this->tenantB->name);
    }

    public function test_tenant_settings_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(TenantSetting::class, fn () => TenantSetting::create(['key' => 'company_name', 'value' => 'B']));
    }

    public function test_setup_step_states_are_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(SetupStepState::class, fn () => SetupStepState::create(['step_key' => 'pay_components', 'state' => 'reviewed']));
    }

    public function test_a_branch_with_staff_can_be_deactivated_but_not_deleted(): void
    {
        $branchId = $this->actingAs($this->userA, 'sanctum')->postJson('/api/branches/create', ['name' => 'North'])->assertCreated()->json('data.id');

        // Its creator automatically belongs to it — so it has staff.
        $this->inTenantA(fn () => $this->assertTrue(UserBranch::where('user_id', $this->userA->id)->where('branch_id', $branchId)->exists()));

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/branches/delete', ['id' => $branchId])->assertStatus(409);
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/branches/update', ['id' => $branchId, 'name' => 'North', 'status' => false])->assertOk();

        $this->inTenantA(fn () => $this->assertFalse(Branch::findOrFail($branchId)->status));
    }

    public function test_another_companys_branch_cannot_be_edited(): void
    {
        $branchB = $this->tenantContext()->runAs($this->tenantB->id, fn () => Branch::factory()->for($this->tenantB)->create());

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/branches/update', ['id' => $branchB->id, 'name' => 'Hijacked'])
            ->assertUnprocessable();
    }

    public function test_new_salaries_default_to_the_company_currency_setting(): void
    {
        $this->inTenantA(fn () => TenantSetting::set('currency', 'GBP'));
        $basicId = $this->inTenantA(fn () => PayComponent::basic()->id);

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/payroll/salaries/set', [
            'user_id' => $this->userA->id,
            'effective_from' => '2026-01-01',
            'pay_frequency' => 'monthly',
            'components' => [['pay_component_id' => $basicId, 'value' => 3000]],
        ])->assertCreated()->assertJsonPath('data.currency', 'GBP');
    }
}
