<?php

namespace Tests\Feature\Hr;

use App\Models\User;
use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Hr\Models\StaffOffboardRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class StaffOffboardingTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected User $approverA;

    protected User $leaver;

    protected int $resignationId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        Permission::findOrCreate('HRMS-StaffManagement', 'sanctum');
        Permission::findOrCreate('HRMS-Approval-L2', 'sanctum');

        $this->inTenantA(function () {
            $this->userA->givePermissionTo('HRMS-StaffManagement');

            $this->approverA = User::factory()->for($this->tenantA)->create(['status' => 'active']);
            $this->approverA->givePermissionTo(['HRMS-StaffManagement', 'HRMS-Approval-L2']);

            $this->leaver = User::factory()->for($this->tenantA)->create(['status' => 'active']);
            $this->resignationId = DepartureReason::where('name', 'Resignation')->value('id');
        });
    }

    protected function inTenantA(\Closure $callback): mixed
    {
        return $this->tenantContext()->runAs($this->tenantA->id, $callback);
    }

    protected function offboard(User $actor, array $overrides = [])
    {
        return $this->actingAs($actor, 'sanctum')->postJson('/api/staff/offboard', array_merge([
            'id' => $this->leaver->id,
            'last_working_day' => now()->toDateString(),
            'departure_reason_id' => $this->resignationId,
        ], $overrides));
    }

    protected function reportTo(User $manager, int $count = 1): array
    {
        return $this->inTenantA(fn () => User::factory()->for($this->tenantA)->count($count)->create([
            'status' => 'active',
            'hierarchy_parent_id' => $manager->id,
        ])->all());
    }

    // SLMS360 hole: offboardStaff had no permission check at all.
    public function test_staff_without_permission_cannot_offboard_anyone(): void
    {
        $colleague = $this->inTenantA(fn () => User::factory()->for($this->tenantA)->create(['status' => 'active']));

        $this->offboard($colleague)->assertForbidden();
    }

    public function test_nobody_can_offboard_themselves(): void
    {
        $this->offboard($this->approverA, ['id' => $this->approverA->id])->assertStatus(409);
    }

    public function test_offboarding_without_l2_waits_for_approval_then_applies(): void
    {
        $this->inTenantA(fn () => $this->leaver->createToken('app'));

        $response = $this->offboard($this->userA, ['note' => 'Moving abroad'])
            ->assertCreated()
            ->assertJsonPath('data.requires_approval', true);

        $this->inTenantA(fn () => $this->assertSame('active', $this->leaver->fresh()->status));

        $this->actingAs($this->approverA, 'sanctum')
            ->postJson('/api/staff/offboard/approve', ['id' => $response->json('data.id')])
            ->assertOk();

        $this->inTenantA(function () {
            $leaver = $this->leaver->fresh();
            $this->assertSame('offboarded', $leaver->status);
            $this->assertSame('Resignation: Moving abroad', $leaver->left_reason);
            $this->assertSame($this->resignationId, $leaver->departure_reason_id);
            $this->assertSame(0, $leaver->tokens()->count(), 'An offboarded user must lose API access.');
        });
    }

    public function test_a_rejected_offboarding_never_touches_the_staff_member(): void
    {
        $requestId = $this->offboard($this->userA)->assertCreated()->json('data.id');

        $this->actingAs($this->approverA, 'sanctum')
            ->postJson('/api/staff/offboard/reject', ['id' => $requestId, 'reason' => 'Counter-offer accepted'])
            ->assertOk();

        $this->inTenantA(function () {
            $leaver = $this->leaver->fresh();
            $this->assertSame('active', $leaver->status);
            $this->assertNull($leaver->lefted_at);
        });
    }

    public function test_a_manager_cannot_be_offboarded_without_reassigning_their_reports(): void
    {
        $this->reportTo($this->leaver, 2);

        $this->offboard($this->approverA)
            ->assertUnprocessable()
            ->assertJsonPath('requires_manager_reassignment', true)
            ->assertJsonPath('direct_reports_count', 2);
    }

    public function test_a_replacement_manager_from_another_tenant_is_rejected(): void
    {
        $this->reportTo($this->leaver);

        $this->offboard($this->approverA, ['new_manager_id' => $this->userB->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('new_manager_id');
    }

    public function test_promoting_a_direct_report_moves_the_rest_of_the_team_under_them(): void
    {
        [$promoted, $teammate] = $this->reportTo($this->leaver, 2);
        $this->inTenantA(fn () => $this->leaver->update(['hierarchy_parent_id' => $this->approverA->id]));

        $this->offboard($this->approverA, ['new_manager_id' => $promoted->id])->assertCreated();

        $this->inTenantA(function () use ($promoted, $teammate) {
            $this->assertSame($this->approverA->id, $promoted->fresh()->hierarchy_parent_id);
            $this->assertSame($promoted->id, $teammate->fresh()->hierarchy_parent_id);
        });
    }

    public function test_a_future_exit_is_scheduled_and_applied_by_the_nightly_run(): void
    {
        [$report] = $this->reportTo($this->leaver);
        $newManager = $this->inTenantA(fn () => User::factory()->for($this->tenantA)->create(['status' => 'active']));

        $this->offboard($this->approverA, [
            'last_working_day' => now()->addDays(3)->toDateString(),
            'new_manager_id' => $newManager->id,
        ])->assertCreated()->assertJsonPath('data.requires_approval', false);

        $this->inTenantA(function () use ($report) {
            $this->assertSame('active', $this->leaver->fresh()->status, 'Still working until their last day.');
            $this->assertSame($this->leaver->id, $report->fresh()->hierarchy_parent_id, 'Reports stay put until they leave.');
        });

        $this->travel(4)->days();
        $this->artisan('staff:process-offboarding')->assertSuccessful();

        $this->inTenantA(function () use ($report, $newManager) {
            $this->assertSame('offboarded', $this->leaver->fresh()->status);
            $this->assertSame($newManager->id, $report->fresh()->hierarchy_parent_id);
        });
    }

    public function test_a_second_offboarding_for_the_same_person_is_refused(): void
    {
        $this->offboard($this->userA)->assertCreated();
        $this->offboard($this->userA)->assertStatus(409);
    }

    public function test_cancelling_withdraws_a_pending_request(): void
    {
        $requestId = $this->offboard($this->userA)->json('data.id');

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/staff/cancel-offboarding', ['id' => $this->leaver->id])
            ->assertOk();

        $this->inTenantA(fn () => $this->assertSame('cancelled', StaffOffboardRequest::findOrFail($requestId)->status));
    }

    public function test_cancelling_undoes_a_scheduled_exit(): void
    {
        $requestId = $this->offboard($this->approverA, ['last_working_day' => now()->addWeek()->toDateString()])->json('data.id');

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/staff/cancel-offboarding', ['id' => $this->leaver->id])
            ->assertOk();

        $this->inTenantA(function () use ($requestId) {
            $this->assertNull($this->leaver->fresh()->lefted_at);
            $this->assertSame('cancelled', StaffOffboardRequest::findOrFail($requestId)->status);
        });
    }

    public function test_the_other_reason_requires_a_note(): void
    {
        $otherId = $this->inTenantA(fn () => DepartureReason::where('name', 'Other')->value('id'));

        $this->offboard($this->userA, ['departure_reason_id' => $otherId])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');
    }

    public function test_approval_fails_if_the_replacement_manager_left_in_the_meantime(): void
    {
        $this->reportTo($this->leaver);
        $newManager = $this->inTenantA(fn () => User::factory()->for($this->tenantA)->create(['status' => 'active']));

        $requestId = $this->offboard($this->userA, ['new_manager_id' => $newManager->id])->json('data.id');

        $this->inTenantA(fn () => $newManager->update(['status' => 'offboarded']));

        $this->actingAs($this->approverA, 'sanctum')
            ->postJson('/api/staff/offboard/approve', ['id' => $requestId])
            ->assertStatus(409);
    }
}
