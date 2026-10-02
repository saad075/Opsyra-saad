<?php

namespace Tests\Feature\Crm;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class LeadTypeControllerTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        Permission::findOrCreate('CRM-ManageLeads', 'sanctum');
    }

    protected function givePermission(): void
    {
        $this->tenantContext()->runAs($this->tenantA->id, fn () => $this->userA->givePermissionTo('CRM-ManageLeads'));
    }

    public function test_any_authenticated_staff_member_can_list_types(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->getJson('/api/lead-types/list');

        $response->assertOk();
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_a_user_without_the_permission_cannot_create_a_type(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-types/create', ['name' => 'Upsell']);

        $response->assertForbidden();
    }

    public function test_it_creates_and_updates_a_type(): void
    {
        $this->givePermission();

        $create = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-types/create', ['name' => 'Upsell']);
        $create->assertCreated();

        $update = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-types/update', ['id' => $create->json('data.id'), 'name' => 'Upsells']);
        $update->assertOk();
        $update->assertJsonPath('data.name', 'Upsells');
    }

    public function test_a_type_already_used_on_a_lead_cannot_be_deleted(): void
    {
        $this->givePermission();

        $type = $this->asTenantOf($this->userA, function () {
            $type = LeadType::factory()->for($this->tenantA)->create();
            Lead::factory()->for($this->tenantA)->create(['lead_type_id' => $type->id]);

            return $type;
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-types/delete', ['id' => $type->id]);

        $response->assertStatus(409);
    }

    public function test_it_cannot_update_a_type_belonging_to_another_tenant(): void
    {
        $this->givePermission();

        $typeB = $this->asTenantOf($this->userB, fn () => LeadType::factory()->for($this->tenantB)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-types/update', ['id' => $typeB->id, 'name' => 'Hijacked']);

        $response->assertUnprocessable();
    }
}
