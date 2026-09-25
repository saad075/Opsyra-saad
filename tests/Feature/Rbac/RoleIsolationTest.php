<?php

namespace Tests\Feature\Rbac;

use App\Modules\Rbac\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsTenantIsolation;
use Tests\TestCase;

class RoleIsolationTest extends TestCase
{
    use AssertsTenantIsolation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    public function test_roles_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            Role::class,
            fn () => Role::create(['name' => 'Team Lead', 'guard_name' => 'sanctum']),
        );
    }

    public function test_the_same_role_name_is_allowed_in_different_tenants(): void
    {
        $roleA = $this->tenantContext()->runAs(
            $this->tenantA->id,
            fn () => Role::create(['name' => 'Owner', 'guard_name' => 'sanctum'])
        );

        $roleB = $this->tenantContext()->runAs(
            $this->tenantB->id,
            fn () => Role::create(['name' => 'Owner', 'guard_name' => 'sanctum'])
        );

        $this->assertNotSame($roleA->id, $roleB->id);
        $this->assertSame('Owner', $roleA->name);
        $this->assertSame('Owner', $roleB->name);
    }
}
