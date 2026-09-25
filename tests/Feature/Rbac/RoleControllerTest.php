<?php

namespace Tests\Feature\Rbac;

use App\Modules\Rbac\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        Permission::create(['name' => 'HRMS-RoleManagement', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'HRMS-StaffManagement', 'guard_name' => 'sanctum']);
    }

    protected function givePermission(): void
    {
        $this->tenantContext()->runAs(
            $this->tenantA->id,
            fn () => $this->userA->givePermissionTo('HRMS-RoleManagement')
        );
    }

    public function test_a_user_without_the_permission_cannot_list_roles(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->getJson('/api/roles/list');

        $response->assertForbidden();
    }

    public function test_a_user_with_the_permission_can_create_and_list_a_role(): void
    {
        $this->givePermission();

        $create = $this->actingAs($this->userA, 'sanctum')->postJson('/api/roles/create', [
            'name' => 'HR Manager',
            'permissions' => ['HRMS-StaffManagement'],
        ]);

        $create->assertCreated();

        $list = $this->actingAs($this->userA, 'sanctum')->getJson('/api/roles/list');

        $list->assertOk();
        $list->assertJsonPath('data.0.name', 'HR Manager');
        $list->assertJsonPath('data.0.permissions.0.name', 'HRMS-StaffManagement');
    }

    public function test_it_rejects_a_duplicate_role_name_within_the_same_tenant(): void
    {
        $this->givePermission();

        $this->tenantContext()->runAs(
            $this->tenantA->id,
            fn () => Role::create(['name' => 'HR Manager', 'guard_name' => 'sanctum'])
        );

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/roles/create', [
            'name' => 'HR Manager',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }

    public function test_it_cannot_update_a_role_belonging_to_another_tenant(): void
    {
        $this->givePermission();

        $roleB = $this->tenantContext()->runAs(
            $this->tenantB->id,
            fn () => Role::create(['name' => 'Owner', 'guard_name' => 'sanctum'])
        );

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/roles/update', [
            'id' => $roleB->id,
            'name' => 'Hijacked',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('id');
    }

    public function test_it_prevents_deleting_a_role_still_assigned_to_staff(): void
    {
        $this->givePermission();

        $role = $this->tenantContext()->runAs($this->tenantA->id, function () {
            $role = Role::create(['name' => 'HR Manager', 'guard_name' => 'sanctum']);
            $this->userA->assignRole($role);

            return $role;
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/roles/delete', [
            'id' => $role->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', false);

        // The request's tenant context is cleared in terminate() once the
        // response is sent (see ResolveTenantFromUser) — as it should be, so
        // it can't leak into the next request on a reused PHP process. A
        // post-request assertion against a tenant-scoped table needs its own
        // context, the same as any other out-of-request code would.
        $this->tenantContext()->runAs($this->tenantA->id, function () use ($role) {
            $this->assertDatabaseHas('roles', ['id' => $role->id]);
        });
    }

    public function test_it_deletes_an_unassigned_role(): void
    {
        $this->givePermission();

        $role = $this->tenantContext()->runAs(
            $this->tenantA->id,
            fn () => Role::create(['name' => 'Unused Role', 'guard_name' => 'sanctum'])
        );

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/roles/delete', [
            'id' => $role->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
    }
}
