<?php

namespace Tests\Feature\Crm;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadEmail;
use App\Modules\Crm\Notifications\LeadEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class LeadMailControllerTest extends TestCase
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

    public function test_a_user_without_the_permission_cannot_send_mail(): void
    {
        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-mail/send', [
            'lead_id' => $lead->id, 'to_email' => 'jane@acme.test', 'subject' => 'Hi', 'body' => 'Hello there.',
        ]);

        $response->assertForbidden();
    }

    public function test_it_sends_and_logs_an_email(): void
    {
        Notification::fake();
        $this->givePermission();

        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-mail/send', [
            'lead_id' => $lead->id,
            'to_email' => 'jane@acme.test',
            'subject' => 'Following up',
            'body' => 'Just checking in on the proposal.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.to_email', 'jane@acme.test');

        Notification::assertSentTo(
            new AnonymousNotifiable,
            LeadEmailNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'jane@acme.test'
                && $notification->subject === 'Following up',
        );

        $this->asTenantOf($this->userA, function () use ($lead) {
            $this->assertSame(1, LeadEmail::where('lead_id', $lead->id)->count());
        });
    }

    public function test_it_rejects_an_invalid_recipient_address(): void
    {
        $this->givePermission();

        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-mail/send', [
            'lead_id' => $lead->id, 'to_email' => 'not-an-email', 'subject' => 'Hi', 'body' => 'Hello.',
        ]);

        $response->assertUnprocessable();
    }

    public function test_it_rejects_a_lead_id_belonging_to_another_tenant(): void
    {
        $this->givePermission();

        $leadB = $this->asTenantOf($this->userB, fn () => Lead::factory()->for($this->tenantB)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-mail/send', [
            'lead_id' => $leadB->id, 'to_email' => 'jane@acme.test', 'subject' => 'Hi', 'body' => 'Hello.',
        ]);

        $response->assertUnprocessable();
    }
}
