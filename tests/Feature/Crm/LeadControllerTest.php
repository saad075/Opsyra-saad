<?php

namespace Tests\Feature\Crm;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadPipeline;
use App\Modules\Crm\Models\LeadPipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class LeadControllerTest extends TestCase
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

    public function test_a_new_lead_lands_on_the_default_pipelines_first_stage(): void
    {
        $this->givePermission();

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/leads/create', [
            'title' => 'Acme — platform migration',
            'contact_name' => 'Jane Prospect',
            'contact_email' => 'jane@acme.test',
        ]);

        $response->assertCreated();

        $this->asTenantOf($this->userA, function () use ($response) {
            $pipeline = LeadPipeline::where('is_default', true)->first();
            $firstStage = $pipeline->stages()->orderBy('sort_order')->first();

            $this->assertSame($pipeline->id, $response->json('data.lead_pipeline_id'));
            $this->assertSame($firstStage->id, $response->json('data.lead_pipeline_stage_id'));
        });
    }

    public function test_a_user_without_the_permission_cannot_create_a_lead(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/leads/create', ['title' => 'Should fail']);

        $response->assertForbidden();
    }

    public function test_moving_to_a_won_stage_sets_status_and_closed_at(): void
    {
        $this->givePermission();

        [$lead, $wonStageId] = $this->asTenantOf($this->userA, function () {
            $lead = Lead::factory()->for($this->tenantA)->create();
            $wonStage = LeadPipelineStage::where('lead_pipeline_id', $lead->lead_pipeline_id)->where('code', 'won')->first();

            return [$lead, $wonStage->id];
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/leads/move', [
            'id' => $lead->id,
            'lead_pipeline_stage_id' => $wonStageId,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', true);
        $this->assertNotNull($response->json('data.closed_at'));
    }

    public function test_moving_to_a_lost_stage_without_a_reason_is_refused(): void
    {
        $this->givePermission();

        [$lead, $lostStageId] = $this->asTenantOf($this->userA, function () {
            $lead = Lead::factory()->for($this->tenantA)->create();
            $lostStage = LeadPipelineStage::where('lead_pipeline_id', $lead->lead_pipeline_id)->where('code', 'lost')->first();

            return [$lead, $lostStage->id];
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/leads/move', [
            'id' => $lead->id,
            'lead_pipeline_stage_id' => $lostStageId,
        ]);

        $response->assertStatus(422);
    }

    public function test_moving_to_a_lost_stage_with_a_reason_closes_it(): void
    {
        $this->givePermission();

        [$lead, $lostStageId] = $this->asTenantOf($this->userA, function () {
            $lead = Lead::factory()->for($this->tenantA)->create();
            $lostStage = LeadPipelineStage::where('lead_pipeline_id', $lead->lead_pipeline_id)->where('code', 'lost')->first();

            return [$lead, $lostStage->id];
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/leads/move', [
            'id' => $lead->id,
            'lead_pipeline_stage_id' => $lostStageId,
            'lost_reason' => 'Went with a competitor.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', false);
        $response->assertJsonPath('data.lost_reason', 'Went with a competitor.');
    }

    public function test_moving_a_lost_lead_back_to_an_open_stage_reopens_it(): void
    {
        $this->givePermission();

        [$lead, $openStageId] = $this->asTenantOf($this->userA, function () {
            $lead = Lead::factory()->for($this->tenantA)->create();
            $stages = LeadPipelineStage::where('lead_pipeline_id', $lead->lead_pipeline_id)->get();
            $lostStage = $stages->firstWhere('code', 'lost');
            $openStage = $stages->firstWhere('code', 'qualified');

            $lead->moveToStage($lostStage, 'Not interested right now.');

            return [$lead, $openStage->id];
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/leads/move', [
            'id' => $lead->id,
            'lead_pipeline_stage_id' => $openStageId,
        ]);

        $response->assertOk();
        $this->assertNull($response->json('data.status'));
        $this->assertNull($response->json('data.closed_at'));
        $this->assertNull($response->json('data.lost_reason'));
    }

    public function test_updating_a_lead_cannot_change_its_stage_directly(): void
    {
        $this->givePermission();

        [$lead, $wonStageId] = $this->asTenantOf($this->userA, function () {
            $lead = Lead::factory()->for($this->tenantA)->create();
            $wonStage = LeadPipelineStage::where('lead_pipeline_id', $lead->lead_pipeline_id)->where('code', 'won')->first();

            return [$lead, $wonStage->id];
        });
        $originalStageId = $lead->lead_pipeline_stage_id;

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/leads/update', [
            'id' => $lead->id,
            'title' => $lead->title,
            'lead_pipeline_stage_id' => $wonStageId,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.lead_pipeline_stage_id', $originalStageId);
        $this->assertNull($response->json('data.status'));
    }

    public function test_it_filters_by_status(): void
    {
        $this->givePermission();

        $this->asTenantOf($this->userA, function () {
            $open = Lead::factory()->for($this->tenantA)->create(['title' => 'Open deal']);
            $wonStage = LeadPipelineStage::where('lead_pipeline_id', $open->lead_pipeline_id)->where('code', 'won')->first();
            Lead::factory()->for($this->tenantA)->create(['title' => 'Won deal'])->moveToStage($wonStage);
        });

        $response = $this->actingAs($this->userA, 'sanctum')->getJson('/api/leads/list?status=won');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Won deal'));
        $this->assertFalse($titles->contains('Open deal'));
    }

    public function test_it_cannot_update_a_lead_belonging_to_another_tenant(): void
    {
        $this->givePermission();

        $leadB = $this->asTenantOf($this->userB, fn () => Lead::factory()->for($this->tenantB)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/leads/update', ['id' => $leadB->id, 'title' => 'Hijacked']);

        $response->assertUnprocessable();
    }

    public function test_it_deletes_a_lead(): void
    {
        $this->givePermission();

        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/leads/delete', ['id' => $lead->id]);

        $response->assertOk();
        $this->asTenantOf($this->userA, fn () => $this->assertNull(Lead::find($lead->id)));
    }
}
