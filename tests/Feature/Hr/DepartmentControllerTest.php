<?php

namespace Tests\Feature\Hr;

use App\Modules\Hr\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        Permission::findOrCreate('HRMS-StaffManagement', 'sanctum');
    }

    protected function givePermission(): void
    {
        $this->tenantContext()->runAs(
            $this->tenantA->id,
            fn () => $this->userA->givePermissionTo('HRMS-StaffManagement')
        );
    }

    public function test_any_authenticated_staff_member_can_list_departments(): void
    {
        $this->tenantContext()->runAs(
            $this->tenantA->id,
            fn () => Department::create(['name' => 'Engineering'])
        );

        $response = $this->actingAs($this->userA, 'sanctum')->getJson('/api/departments/list');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Engineering');
    }

    public function test_a_user_without_the_permission_cannot_create_a_department(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/departments/create', [
            'name' => 'Engineering',
        ]);

        $response->assertForbidden();
    }

    public function test_it_rejects_a_duplicate_department_name_within_the_same_tenant(): void
    {
        $this->givePermission();

        $this->tenantContext()->runAs(
            $this->tenantA->id,
            fn () => Department::create(['name' => 'Engineering'])
        );

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/departments/create', [
            'name' => 'Engineering',
        ]);

        $response->assertUnprocessable();
    }

    public function test_it_prevents_deleting_a_department_that_still_has_designations(): void
    {
        $this->givePermission();

        $department = $this->tenantContext()->runAs($this->tenantA->id, function () {
            $department = Department::create(['name' => 'Engineering']);
            $department->designations()->create(['name' => 'Backend Engineer']);

            return $department;
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/departments/delete', [
            'id' => $department->id,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('status', false);
    }

    public function test_it_cannot_update_a_department_belonging_to_another_tenant(): void
    {
        $this->givePermission();

        $departmentB = $this->tenantContext()->runAs(
            $this->tenantB->id,
            fn () => Department::create(['name' => 'Sales'])
        );

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/departments/update', [
            'id' => $departmentB->id,
            'name' => 'Hijacked',
        ]);

        $response->assertUnprocessable();
    }
}
