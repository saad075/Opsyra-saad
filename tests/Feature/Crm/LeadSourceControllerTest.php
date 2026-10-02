<?php

namespace Tests\Feature\Crm;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class LeadSourceControllerTest extends TestCase
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

    public function test_any_authenticated_staff_member_can_list_sources(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->getJson('/api/lead-sources/list');

        $response->assertOk();
        // Backfilled by the create_lead_sources_table migration itself.
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_a_user_without_the_permission_cannot_create_a_source(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-sources/create', ['name' => 'Trade Show']);

        $response->assertForbidden();
    }

    public function test_it_creates_and_updates_a_source(): void
    {
        $this->givePermission();

        $create = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-sources/create', ['name' => 'Trade Show']);
        $create->assertCreated();
        $id = $create->json('data.id');

        $update = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-sources/update', ['id' => $id, 'name' => 'Trade Shows', 'is_active' => false]);
        $update->assertOk();
        $update->assertJsonPath('data.name', 'Trade Shows');
        $update->assertJsonPath('data.is_active', false);
    }

    public function test_it_rejects_a_duplicate_name_within_the_same_tenant(): void
    {
        $this->givePermission();

        $this->asTenantOf($this->userA, fn () => LeadSource::factory()->for($this->tenantA)->create(['name' => 'Trade Show']));

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-sources/create', ['name' => 'Trade Show']);

        $response->assertUnprocessable();
    }

    public function test_a_system_source_cannot_be_deleted(): void
    {
        $this->givePermission();

        $source = $this->asTenantOf($this->userA, fn () => LeadSource::where('is_system', true)->first());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-sources/delete', ['id' => $source->id]);

        $response->assertStatus(409);
    }

    public function test_a_source_already_used_on_a_lead_cannot_be_deleted(): void
    {
        $this->givePermission();

        $source = $this->asTenantOf($this->userA, function () {
            $source = LeadSource::factory()->for($this->tenantA)->create();
            Lead::factory()->for($this->tenantA)->create(['lead_source_id' => $source->id]);

            return $source;
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-sources/delete', ['id' => $source->id]);

        $response->assertStatus(409);
    }

    public function test_an_unused_custom_source_can_be_deleted(): void
    {
        $this->givePermission();

        $source = $this->asTenantOf($this->userA, fn () => LeadSource::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-sources/delete', ['id' => $source->id]);

        $response->assertOk();
    }

    public function test_it_cannot_update_a_source_belonging_to_another_tenant(): void
    {
        $this->givePermission();

        $sourceB = $this->asTenantOf($this->userB, fn () => LeadSource::factory()->for($this->tenantB)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-sources/update', ['id' => $sourceB->id, 'name' => 'Hijacked']);

        $response->assertUnprocessable();
    }
}
