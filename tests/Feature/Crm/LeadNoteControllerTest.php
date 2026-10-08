<?php

namespace Tests\Feature\Crm;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class LeadNoteControllerTest extends TestCase
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

    public function test_a_user_without_the_permission_cannot_add_a_note(): void
    {
        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-notes/create', ['lead_id' => $lead->id, 'body' => 'Called, left a voicemail.']);

        $response->assertForbidden();
    }

    public function test_it_adds_a_note_to_a_lead(): void
    {
        $this->givePermission();

        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-notes/create', [
            'lead_id' => $lead->id,
            'body' => 'Called, left a voicemail.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.body', 'Called, left a voicemail.');
        $response->assertJsonPath('data.author.name', $this->userA->name);
    }

    public function test_it_rejects_a_lead_id_belonging_to_another_tenant(): void
    {
        $this->givePermission();

        $leadB = $this->asTenantOf($this->userB, fn () => Lead::factory()->for($this->tenantB)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-notes/create', [
            'lead_id' => $leadB->id,
            'body' => 'Should never attach.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_it_updates_and_deletes_a_note(): void
    {
        $this->givePermission();

        $note = $this->asTenantOf($this->userA, fn () => LeadNote::factory()->for($this->tenantA)->create());

        $update = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-notes/update', ['id' => $note->id, 'body' => 'Edited.']);
        $update->assertOk();
        $update->assertJsonPath('data.body', 'Edited.');

        $delete = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-notes/delete', ['id' => $note->id]);
        $delete->assertOk();

        $this->asTenantOf($this->userA, fn () => $this->assertNull(LeadNote::find($note->id)));
    }

    public function test_it_cannot_update_a_note_belonging_to_another_tenant(): void
    {
        $this->givePermission();

        $noteB = $this->asTenantOf($this->userB, fn () => LeadNote::factory()->for($this->tenantB)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-notes/update', ['id' => $noteB->id, 'body' => 'Hijacked']);

        $response->assertUnprocessable();
    }

    public function test_show_includes_notes_newest_first(): void
    {
        $this->givePermission();

        $lead = $this->asTenantOf($this->userA, function () {
            $lead = Lead::factory()->for($this->tenantA)->create();
            $lead->notes()->create(['body' => 'First note.', 'created_by' => $this->userA->id]);
            $lead->notes()->create(['body' => 'Second note.', 'created_by' => $this->userA->id]);

            return $lead;
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/leads/show', ['id' => $lead->id]);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.notes'));
        $this->assertSame('Second note.', $response->json('data.notes.0.body'));
    }
}
