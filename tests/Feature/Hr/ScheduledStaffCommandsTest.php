<?php

namespace Tests\Feature\Hr;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

/**
 * The nightly commands run outside any request, so they must switch into
 * each tenant themselves — under RLS a command with no tenant context sees
 * no rows at all. These prove both tenants get processed.
 */
class ScheduledStaffCommandsTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    public function test_activate_pending_activates_future_joiners_in_every_tenant(): void
    {
        $joinerA = $this->tenantContext()->runAs($this->tenantA->id, fn () => User::factory()->for($this->tenantA)->create([
            'status' => 'pending', 'joined_at' => today(),
        ]));
        $joinerB = $this->tenantContext()->runAs($this->tenantB->id, fn () => User::factory()->for($this->tenantB)->create([
            'status' => 'pending', 'joined_at' => today(),
        ]));
        $notYet = $this->tenantContext()->runAs($this->tenantA->id, fn () => User::factory()->for($this->tenantA)->create([
            'status' => 'pending', 'joined_at' => today()->addDay(),
        ]));

        $this->artisan('staff:activate-pending')->assertSuccessful();

        $this->tenantContext()->runAs($this->tenantA->id, function () use ($joinerA, $notYet) {
            $this->assertSame('active', $joinerA->fresh()->status);
            $this->assertSame('pending', $notYet->fresh()->status);
        });
        $this->tenantContext()->runAs($this->tenantB->id, fn () => $this->assertSame('active', $joinerB->fresh()->status));
    }

    public function test_activate_pending_skips_someone_whose_exit_date_already_passed(): void
    {
        $leftEarly = $this->tenantContext()->runAs($this->tenantA->id, fn () => User::factory()->for($this->tenantA)->create([
            'status' => 'pending', 'joined_at' => today(), 'lefted_at' => today()->subDay(),
        ]));

        $this->artisan('staff:activate-pending')->assertSuccessful();

        $this->tenantContext()->runAs($this->tenantA->id, fn () => $this->assertSame('pending', $leftEarly->fresh()->status));
    }

    public function test_process_offboarding_handles_every_tenant(): void
    {
        $leaverB = $this->tenantContext()->runAs($this->tenantB->id, fn () => User::factory()->for($this->tenantB)->create([
            'status' => 'active', 'lefted_at' => today()->subDay(),
        ]));

        $this->artisan('staff:process-offboarding')->assertSuccessful();

        $this->tenantContext()->runAs($this->tenantB->id, fn () => $this->assertSame('offboarded', $leaverB->fresh()->status));
    }
}
