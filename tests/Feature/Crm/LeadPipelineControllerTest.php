<?php

namespace Tests\Feature\Crm;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadPipeline;
use App\Modules\Crm\Models\LeadPipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class LeadPipelineControllerTest extends TestCase
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

    public function test_any_authenticated_staff_member_can_list_pipelines_with_stages(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->getJson('/api/lead-pipelines/list');

        $response->assertOk();
        // Backfilled by create_lead_pipeline_stages_table's migration (one default pipeline, 7 stages).
        $response->assertJsonPath('data.0.name', 'Sales Pipeline');
        $this->assertCount(7, $response->json('data.0.stages'));
    }

    public function test_making_a_new_pipeline_default_unsets_the_previous_default(): void
    {
        $this->givePermission();

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-pipelines/create', ['name' => 'Partner Pipeline', 'is_default' => true]);
        $response->assertCreated();

        $this->asTenantOf($this->userA, function () {
            $this->assertSame(1, LeadPipeline::where('is_default', true)->count());
            $this->assertSame('Partner Pipeline', LeadPipeline::where('is_default', true)->first()->name);
        });
    }

    public function test_the_only_remaining_pipeline_cannot_be_deleted(): void
    {
        $this->givePermission();

        $pipeline = $this->asTenantOf($this->userA, fn () => LeadPipeline::where('is_default', true)->first());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-pipelines/delete', ['id' => $pipeline->id]);

        $response->assertStatus(409);
    }

    public function test_a_pipeline_with_leads_in_it_cannot_be_deleted(): void
    {
        $this->givePermission();

        [$pipeline, $other] = $this->asTenantOf($this->userA, function () {
            $pipeline = LeadPipeline::factory()->for($this->tenantA)->create();
            Lead::factory()->for($this->tenantA)->create(['lead_pipeline_id' => $pipeline->id]);

            return [$pipeline, null];
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-pipelines/delete', ['id' => $pipeline->id]);

        $response->assertStatus(409);
    }

    public function test_it_creates_updates_and_deletes_a_stage(): void
    {
        $this->givePermission();

        // A second pre-existing stage, so deleting the one created below
        // doesn't trip the "a pipeline needs at least one stage" guard.
        $pipeline = $this->asTenantOf($this->userA, function () {
            $pipeline = LeadPipeline::factory()->for($this->tenantA)->create();
            LeadPipelineStage::factory()->for($this->tenantA)->create(['lead_pipeline_id' => $pipeline->id]);

            return $pipeline;
        });

        $create = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-pipelines/stages/create', [
            'lead_pipeline_id' => $pipeline->id,
            'code' => 'demo-booked',
            'name' => 'Demo Booked',
            'probability' => 35,
        ]);
        $create->assertCreated();
        $stageId = $create->json('data.id');

        $update = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-pipelines/stages/update', [
            'id' => $stageId,
            'name' => 'Demo Scheduled',
            'probability' => 45,
        ]);
        $update->assertOk();
        $update->assertJsonPath('data.name', 'Demo Scheduled');
        // code is immutable — not accepted by updateStage() at all.
        $update->assertJsonPath('data.code', 'demo-booked');

        $delete = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-pipelines/stages/delete', ['id' => $stageId]);
        $delete->assertOk();
    }

    public function test_the_last_stage_in_a_pipeline_cannot_be_deleted(): void
    {
        $this->givePermission();

        $stage = $this->asTenantOf($this->userA, function () {
            $pipeline = LeadPipeline::factory()->for($this->tenantA)->create();

            return LeadPipelineStage::factory()->for($this->tenantA)->create(['lead_pipeline_id' => $pipeline->id]);
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-pipelines/stages/delete', ['id' => $stage->id]);

        $response->assertStatus(409);
    }

    public function test_a_stage_with_leads_in_it_cannot_be_deleted(): void
    {
        $this->givePermission();

        $stage = $this->asTenantOf($this->userA, function () {
            $pipeline = LeadPipeline::factory()->for($this->tenantA)->create();
            $stageA = LeadPipelineStage::factory()->for($this->tenantA)->create(['lead_pipeline_id' => $pipeline->id]);
            LeadPipelineStage::factory()->for($this->tenantA)->create(['lead_pipeline_id' => $pipeline->id]);
            Lead::factory()->for($this->tenantA)->create(['lead_pipeline_id' => $pipeline->id, 'lead_pipeline_stage_id' => $stageA->id]);

            return $stageA;
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-pipelines/stages/delete', ['id' => $stage->id]);

        $response->assertStatus(409);
    }

    public function test_it_rejects_a_stage_in_another_tenants_pipeline(): void
    {
        $this->givePermission();

        $pipelineB = $this->asTenantOf($this->userB, fn () => LeadPipeline::factory()->for($this->tenantB)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-pipelines/stages/create', [
            'lead_pipeline_id' => $pipelineB->id,
            'code' => 'sneaky',
            'name' => 'Sneaky',
        ]);

        $response->assertUnprocessable();
    }
}
