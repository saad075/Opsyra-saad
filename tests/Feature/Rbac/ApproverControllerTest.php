<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use App\Modules\Rbac\Models\Role;
use App\Modules\Rbac\Support\PermissionCatalogue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class ApproverControllerTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    private User $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
        PermissionCatalogue::sync();

        $this->colleague = $this->tenantContext()->runAs($this->tenantA->id, function () {
            $this->userA->update(['status' => 'active']);
            $this->userA->givePermissionTo(['HRMS-StaffPermissionUpdate', 'HRMS-Approval-L2']);

            return User::factory()->for($this->tenantA)->create(['status' => 'active', 'name' => 'Casey Colleague']);
        });
    }

    private function level(User $user): int
    {
        return $this->tenantContext()->runAs($this->tenantA->id, function () use ($user) {
            $user = $user->fresh();

            return $user->hasPermissionTo('HRMS-Approval-L2') ? 2 : ($user->hasPermissionTo('HRMS-Approval-L1') ? 1 : 0);
        });
    }

    public function test_it_lists_people_with_their_approval_level_and_where_it_comes_from(): void
    {
        $this->tenantContext()->runAs($this->tenantA->id, function () {
            $role = Role::create(['name' => 'Payroll checker', 'guard_name' => 'sanctum']);
            $role->givePermissionTo('HRMS-Approval-L1');
            $this->colleague->assignRole($role);
        });

        $rows = collect($this->actingAs($this->userA, 'sanctum')->getJson('/api/approvers/list')->assertOk()->json('data'))->keyBy('id');

        $this->assertSame(['direct_level' => 2, 'role_level' => 0, 'level' => 2], collect($rows[$this->userA->id])->only('direct_level', 'role_level', 'level')->all());
        $this->assertSame(['direct_level' => 0, 'role_level' => 1, 'level' => 1], collect($rows[$this->colleague->id])->only('direct_level', 'role_level', 'level')->all());
        $this->assertSame(['Payroll checker'], $rows[$this->colleague->id]['roles']);
    }

    public function test_it_sets_and_removes_a_direct_level_without_touching_the_role(): void
    {
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/approvers/set', ['user_id' => $this->colleague->id, 'level' => 1])->assertOk();
        $this->assertSame(1, $this->level($this->colleague));

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/approvers/set', ['user_id' => $this->colleague->id, 'level' => 2])->assertOk();
        $this->assertSame(2, $this->level($this->colleague));
        $this->assertFalse($this->tenantContext()->runAs($this->tenantA->id, fn () => $this->colleague->fresh()->hasDirectPermission('HRMS-Approval-L1')));

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/approvers/set', ['user_id' => $this->colleague->id, 'level' => 0])->assertOk();
        $this->assertSame(0, $this->level($this->colleague));
    }

    public function test_it_never_removes_the_last_level_2_approver(): void
    {
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/approvers/set', ['user_id' => $this->userA->id, 'level' => 1])
            ->assertStatus(409);
        $this->assertSame(2, $this->level($this->userA));

        // With someone else at level 2, it's fine.
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/approvers/set', ['user_id' => $this->colleague->id, 'level' => 2])->assertOk();
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/approvers/set', ['user_id' => $this->userA->id, 'level' => 0])->assertOk();
        $this->assertSame(0, $this->level($this->userA));
    }

    public function test_it_needs_the_staff_permissions_permission_and_stays_in_the_tenant(): void
    {
        $this->actingAs($this->colleague, 'sanctum')->getJson('/api/approvers/list')->assertForbidden();
        $this->actingAs($this->colleague, 'sanctum')->postJson('/api/approvers/set', ['user_id' => $this->colleague->id, 'level' => 2])->assertForbidden();

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/approvers/set', ['user_id' => $this->userB->id, 'level' => 2])->assertUnprocessable();
        $this->assertNotContains($this->userB->id, collect($this->actingAs($this->userA, 'sanctum')->getJson('/api/approvers/list')->json('data'))->pluck('id'));
    }

    public function test_the_role_editor_gets_the_permission_catalogue(): void
    {
        $this->tenantContext()->runAs($this->tenantA->id, fn () => $this->userA->givePermissionTo('HRMS-RoleManagement'));

        $this->actingAs($this->userA, 'sanctum')->getJson('/api/roles/permissions')
            ->assertOk()
            ->assertJsonCount(count(PermissionCatalogue::PERMISSIONS), 'data')
            ->assertJsonFragment(['name' => 'HRMS-Approval-L2', 'group' => 'Approvals']);

        $this->actingAs($this->colleague, 'sanctum')->getJson('/api/roles/permissions')->assertForbidden();
    }
}
