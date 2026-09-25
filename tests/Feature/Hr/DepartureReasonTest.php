<?php

namespace Tests\Feature\Hr;

use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class DepartureReasonTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        Permission::create(['name' => 'HRMS-StaffManagement', 'guard_name' => 'sanctum']);
        $this->tenantContext()->runAs($this->tenantA->id, fn () => $this->userA->givePermissionTo('HRMS-StaffManagement'));
    }

    public function test_a_new_tenant_gets_its_countrys_default_reasons(): void
    {
        $uk = Tenant::factory()->create(['country' => 'gb']);
        $us = Tenant::factory()->create(['country' => 'us']);

        $ukNames = $this->tenantContext()->runAs($uk->id, fn () => DepartureReason::pluck('name')->all());
        $usNames = $this->tenantContext()->runAs($us->id, fn () => DepartureReason::pluck('name')->all());

        $this->assertContains('Redundancy', $ukNames);
        $this->assertNotContains('Layoff', $ukNames);
        $this->assertContains('Layoff', $usNames);
        $this->assertContains('Resignation', $usNames);
    }

    public function test_hr_can_add_a_reason_and_inactive_ones_drop_off_the_list(): void
    {
        $id = $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/departure-reasons/create', ['name' => 'Relocation'])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/departure-reasons/update', ['id' => $id, 'name' => 'Relocation', 'is_active' => false])
            ->assertOk();

        $names = collect($this->actingAs($this->userA, 'sanctum')->getJson('/api/departure-reasons/list')->json('data'))->pluck('name');

        $this->assertNotContains('Relocation', $names);
        $this->assertContains('Resignation', $names);
    }

    public function test_built_in_reasons_cannot_be_deleted(): void
    {
        $id = $this->tenantContext()->runAs($this->tenantA->id, fn () => DepartureReason::where('name', 'Resignation')->value('id'));

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/departure-reasons/delete', ['id' => $id])
            ->assertStatus(409);
    }

    public function test_another_tenants_reason_cannot_be_edited(): void
    {
        $idB = $this->tenantContext()->runAs($this->tenantB->id, fn () => DepartureReason::where('name', 'Resignation')->value('id'));

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/departure-reasons/update', ['id' => $idB, 'name' => 'Hijacked'])
            ->assertUnprocessable();
    }
}
