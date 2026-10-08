<?php

namespace Tests\Feature\Crm;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class LeadActivityControllerTest extends TestCase
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

    public function test_a_user_without_the_permission_cannot_log_an_activity(): void
    {
        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-activities/create', [
            'lead_id' => $lead->id, 'type' => 'call', 'title' => 'Follow-up call',
        ]);

        $response->assertForbidden();
    }

    public function test_it_logs_an_activity(): void
    {
        $this->givePermission();

        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-activities/create', [
            'lead_id' => $lead->id,
            'type' => 'meeting',
            'title' => 'Kickoff call',
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.type', 'meeting');
        $response->assertJsonPath('data.is_done', false);
    }

    public function test_it_rejects_an_unknown_type(): void
    {
        $this->givePermission();

        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-activities/create', [
            'lead_id' => $lead->id, 'type' => 'carrier-pigeon', 'title' => 'Should fail',
        ]);

        $response->assertUnprocessable();
    }

    public function test_it_marks_an_activity_done(): void
    {
        $this->givePermission();

        $activity = $this->asTenantOf($this->userA, fn () => LeadActivity::factory()->for($this->tenantA)->create(['is_done' => false]));

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-activities/update', [
            'id' => $activity->id, 'type' => $activity->type, 'title' => $activity->title, 'is_done' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.is_done', true);
    }

    public function test_it_deletes_an_activity(): void
    {
        $this->givePermission();

        $activity = $this->asTenantOf($this->userA, fn () => LeadActivity::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-activities/delete', ['id' => $activity->id]);

        $response->assertOk();
        $this->asTenantOf($this->userA, fn () => $this->assertNull(LeadActivity::find($activity->id)));
    }

    public function test_it_rejects_a_lead_id_belonging_to_another_tenant(): void
    {
        $this->givePermission();

        $leadB = $this->asTenantOf($this->userB, fn () => Lead::factory()->for($this->tenantB)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-activities/create', [
            'lead_id' => $leadB->id, 'type' => 'call', 'title' => 'Should fail',
        ]);

        $response->assertUnprocessable();
    }
}
