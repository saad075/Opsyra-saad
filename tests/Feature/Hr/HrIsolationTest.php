<?php

namespace Tests\Feature\Hr;

use App\Models\User;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\Department;
use App\Modules\Hr\Models\Designation;
use App\Modules\Hr\Models\UserBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsTenantIsolation;
use Tests\TestCase;

class HrIsolationTest extends TestCase
{
    use AssertsTenantIsolation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    public function test_branches_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            Branch::class,
            fn () => Branch::create(['name' => 'Main Branch', 'status' => true]),
        );
    }

    public function test_departments_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            Department::class,
            fn () => Department::create(['name' => 'Engineering']),
        );
    }

    public function test_designations_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            Designation::class,
            fn () => Designation::create(['name' => 'Engineer']),
        );
    }

    public function test_user_branches_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(UserBranch::class, function () {
            $branch = Branch::create(['name' => 'Branch B', 'status' => true]);
            $staff = User::factory()->for($this->tenantB)->create();

            return UserBranch::create(['user_id' => $staff->id, 'branch_id' => $branch->id, 'status' => true]);
        });
    }

    public function test_hierarchy_traversal_never_crosses_tenants(): void
    {
        // Tenant B has a manager with 3 active direct reports — if
        // getAllHierarchyChildrenIds() ever ignored tenant scope, a
        // same-shaped tree in tenant A would leak those rows in too.
        $this->tenantContext()->runAs($this->tenantB->id, function () {
            $managerB = User::factory()->for($this->tenantB)->create(['status' => 'active']);
            User::factory()->for($this->tenantB)->count(3)->create([
                'status' => 'active',
                'hierarchy_parent_id' => $managerB->id,
            ]);
        });

        $this->tenantContext()->runAs($this->tenantA->id, function () {
            $managerA = User::factory()->for($this->tenantA)->create(['status' => 'active']);
            User::factory()->for($this->tenantA)->create([
                'status' => 'active',
                'hierarchy_parent_id' => $managerA->id,
            ]);

            $this->assertCount(1, $managerA->getAllHierarchyChildrenIds());
        });
    }
}
