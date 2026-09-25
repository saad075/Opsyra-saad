<?php

namespace Tests\Feature\Hr;

use App\Models\User;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\StaffOnboardingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class StaffOnboardingTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected Branch $branchA;

    protected User $approverA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        Permission::create(['name' => 'HRMS-StaffManagement', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'HRMS-Approval-L2', 'guard_name' => 'sanctum']);

        $this->branchA = $this->tenantContext()->runAs($this->tenantA->id, fn () => Branch::factory()->for($this->tenantA)->create());

        // userA is HR (staff management, no L2); approverA holds L2.
        $this->approverA = $this->tenantContext()->runAs($this->tenantA->id, function () {
            $this->userA->givePermissionTo('HRMS-StaffManagement');

            $approver = User::factory()->for($this->tenantA)->create();
            $approver->givePermissionTo('HRMS-Approval-L2');

            return $approver;
        });
    }

    protected function createStaffAsHr(array $overrides = []): int
    {
        return $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/create', array_merge([
            'name' => 'New Hire',
            'email' => 'new-hire@example.test',
            'phone' => '555-0100',
            'designation' => 'Engineer',
            'joined_at' => now()->toDateString(),
            'is_remote' => false,
            'dob' => '1990-01-01',
            'branches' => [$this->branchA->id],
        ], $overrides))->assertCreated()->assertJsonPath('data.requires_approval', true)->json('data.id');
    }

    protected function inTenantA(\Closure $callback): mixed
    {
        return $this->tenantContext()->runAs($this->tenantA->id, $callback);
    }

    protected function requestFor(int $staffId): StaffOnboardingRequest
    {
        return $this->inTenantA(fn () => StaffOnboardingRequest::where('user_id', $staffId)->latest('id')->firstOrFail());
    }

    public function test_staff_created_without_l2_waits_for_approval(): void
    {
        $staffId = $this->createStaffAsHr();

        $this->inTenantA(function () use ($staffId) {
            $this->assertSame('pending_approval', User::findOrFail($staffId)->status);
            $this->assertSame('pending', StaffOnboardingRequest::where('user_id', $staffId)->firstOrFail()->status);
        });
    }

    public function test_an_l2_approver_approving_activates_the_staff_member(): void
    {
        $staffId = $this->createStaffAsHr();

        $this->actingAs($this->approverA, 'sanctum')
            ->postJson('/api/staff/onboard/approve', ['id' => $this->requestFor($staffId)->id])
            ->assertOk();

        $this->inTenantA(function () use ($staffId) {
            $this->assertSame('active', User::findOrFail($staffId)->status);
            $this->assertSame('approved', StaffOnboardingRequest::where('user_id', $staffId)->firstOrFail()->status);
        });
    }

    public function test_approval_of_a_future_joiner_leaves_them_pending_until_their_start_date(): void
    {
        $staffId = $this->createStaffAsHr(['joined_at' => now()->addWeek()->toDateString()]);

        $this->actingAs($this->approverA, 'sanctum')
            ->postJson('/api/staff/onboard/approve', ['id' => $this->requestFor($staffId)->id])
            ->assertOk();

        $this->inTenantA(fn () => $this->assertSame('pending', User::findOrFail($staffId)->status));
    }

    public function test_hr_without_l2_cannot_approve(): void
    {
        $staffId = $this->createStaffAsHr();

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/staff/onboard/approve', ['id' => $this->requestFor($staffId)->id])
            ->assertForbidden();
    }

    public function test_a_request_cannot_be_approved_twice(): void
    {
        $requestId = $this->requestFor($this->createStaffAsHr())->id;

        $this->actingAs($this->approverA, 'sanctum')->postJson('/api/staff/onboard/approve', ['id' => $requestId])->assertOk();
        $this->actingAs($this->approverA, 'sanctum')->postJson('/api/staff/onboard/approve', ['id' => $requestId])->assertStatus(409);
    }

    public function test_rejection_waits_for_hr_to_notify_the_candidate_before_offboarding(): void
    {
        $staffId = $this->createStaffAsHr();
        $requestId = $this->requestFor($staffId)->id;

        $this->actingAs($this->approverA, 'sanctum')
            ->postJson('/api/staff/onboard/reject', ['id' => $requestId, 'reason' => 'Failed reference check'])
            ->assertOk();

        $this->inTenantA(fn () => $this->assertSame('pending_approval', User::findOrFail($staffId)->status));

        $this->actingAs($this->userA, 'sanctum')
            ->getJson('/api/staff/onboard/rejected-awaiting-notification')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/staff/onboard/complete-rejected', ['id' => $requestId])
            ->assertOk();

        $this->inTenantA(function () use ($staffId) {
            $staff = User::findOrFail($staffId);
            $this->assertSame('offboarded', $staff->status);
            $this->assertSame('Onboarding rejected: Failed reference check', $staff->left_reason);
        });
    }

    // SLMS360 hole: statusUpdate() set 'active' directly, so HR without L2
    // could reactivate a candidate an approver had just rejected.
    public function test_hr_without_l2_cannot_reactivate_a_rejected_candidate_directly(): void
    {
        $staffId = $this->createStaffAsHr();
        $requestId = $this->requestFor($staffId)->id;

        $this->actingAs($this->approverA, 'sanctum')->postJson('/api/staff/onboard/reject', ['id' => $requestId, 'reason' => 'No'])->assertOk();
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/onboard/complete-rejected', ['id' => $requestId])->assertOk();

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/staff/status-update', ['id' => $staffId])
            ->assertOk()
            ->assertJsonPath('data.requires_approval', true);

        $this->inTenantA(fn () => $this->assertSame('pending_approval', User::findOrFail($staffId)->status));
    }

    public function test_an_approver_cannot_act_on_another_tenants_request(): void
    {
        $approverB = $this->tenantContext()->runAs($this->tenantB->id, function () {
            $approver = User::factory()->for($this->tenantB)->create();
            $approver->givePermissionTo('HRMS-Approval-L2');

            return $approver;
        });

        $requestId = $this->requestFor($this->createStaffAsHr())->id;

        $this->actingAs($approverB, 'sanctum')
            ->postJson('/api/staff/onboard/approve', ['id' => $requestId])
            ->assertUnprocessable();

        $this->actingAs($approverB, 'sanctum')
            ->getJson('/api/staff/onboard/pending-approvals')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
