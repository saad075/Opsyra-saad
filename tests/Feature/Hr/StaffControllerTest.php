<?php

namespace Tests\Feature\Hr;

use App\Models\User;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\UserBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class StaffControllerTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected Branch $branchA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        Permission::create(['name' => 'HRMS-StaffManagement', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'HRMS-StaffPermissionUpdate', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'HRMS-Approval-L2', 'guard_name' => 'sanctum']);

        $this->branchA = $this->tenantContext()->runAs(
            $this->tenantA->id,
            fn () => Branch::factory()->for($this->tenantA)->create()
        );

        // userA "selects" their own branch, matching SLMS360's
        // getMySelectedBranchesId() per-viewer scoping.
        $this->tenantContext()->runAs($this->tenantA->id, function () {
            UserBranch::create(['user_id' => $this->userA->id, 'branch_id' => $this->branchA->id, 'status' => true]);
        });
    }

    protected function givePermission(User $user, string $permission): void
    {
        $this->tenantContext()->runAs($user->tenant_id, fn () => $user->givePermissionTo($permission));
    }

    public function test_a_user_without_the_permission_cannot_create_staff(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/create', [
            'name' => 'New Hire',
            'email' => 'new-hire@example.test',
            'phone' => '555-0100',
            'designation' => 'Engineer',
            'joined_at' => now()->toDateString(),
            'is_remote' => false,
            'dob' => '1990-01-01',
            'branches' => [$this->branchA->id],
        ]);

        $response->assertForbidden();
    }

    // Immediate activation only when the creator is also an L2 approver —
    // the non-L2 path is covered in StaffOnboardingTest.
    public function test_an_l2_approver_creating_staff_activates_them_immediately(): void
    {
        $this->givePermission($this->userA, 'HRMS-StaffManagement');
        $this->givePermission($this->userA, 'HRMS-Approval-L2');

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/create', [
            'name' => 'New Hire',
            'email' => 'new-hire@example.test',
            'phone' => '555-0100',
            'designation' => 'Engineer',
            'joined_at' => now()->toDateString(),
            'is_remote' => false,
            'dob' => '1990-01-01',
            'branches' => [$this->branchA->id],
        ]);

        $response->assertCreated();

        $this->tenantContext()->runAs($this->tenantA->id, function () use ($response) {
            $staff = User::findOrFail($response->json('data.id'));
            $this->assertSame('active', $staff->status);
            $this->assertSame($this->tenantA->id, $staff->tenant_id);
        });
    }

    public function test_a_future_join_date_leaves_the_staff_member_pending(): void
    {
        $this->givePermission($this->userA, 'HRMS-StaffManagement');
        $this->givePermission($this->userA, 'HRMS-Approval-L2');

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/create', [
            'name' => 'Future Hire',
            'email' => 'future-hire@example.test',
            'phone' => '555-0101',
            'designation' => 'Engineer',
            'joined_at' => now()->addWeek()->toDateString(),
            'is_remote' => false,
            'dob' => '1990-01-01',
            'branches' => [$this->branchA->id],
        ]);

        $response->assertCreated();

        $this->tenantContext()->runAs($this->tenantA->id, function () use ($response) {
            $this->assertSame('pending', User::findOrFail($response->json('data.id'))->status);
        });
    }

    public function test_it_rejects_a_branch_id_belonging_to_another_tenant(): void
    {
        $this->givePermission($this->userA, 'HRMS-StaffManagement');

        $branchB = $this->tenantContext()->runAs(
            $this->tenantB->id,
            fn () => Branch::factory()->for($this->tenantB)->create()
        );

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/create', [
            'name' => 'New Hire',
            'email' => 'new-hire@example.test',
            'phone' => '555-0100',
            'designation' => 'Engineer',
            'joined_at' => now()->toDateString(),
            'is_remote' => false,
            'dob' => '1990-01-01',
            'branches' => [$branchB->id],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('branches.0');
    }

    public function test_it_cannot_update_a_staff_member_belonging_to_another_tenant(): void
    {
        $this->givePermission($this->userA, 'HRMS-StaffManagement');

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/update', [
            'user_id' => $this->userB->id,
            'name' => 'Hijacked',
            'email' => $this->userB->email,
            'phone' => '555-0100',
            'is_remote' => false,
            'dob' => '1990-01-01',
            'designation' => 'Engineer',
            'joined_at' => now()->toDateString(),
            'branches' => [$this->branchA->id],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('user_id');
    }

    public function test_updating_permissions_requires_the_staff_permission_update_ability(): void
    {
        $this->givePermission($this->userA, 'HRMS-StaffManagement');

        Permission::create(['name' => 'HRMS-ViewActivityLogs', 'guard_name' => 'sanctum']);

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/update', [
            'user_id' => $this->userA->id,
            'name' => $this->userA->name,
            'email' => $this->userA->email,
            'phone' => '555-0100',
            'is_remote' => false,
            'dob' => '1990-01-01',
            'designation' => 'Engineer',
            'joined_at' => now()->toDateString(),
            'branches' => [$this->branchA->id],
            'permissions' => ['HRMS-ViewActivityLogs'],
        ]);

        $response->assertForbidden();
    }

    public function test_an_l2_approver_reactivates_an_offboarded_staff_member_immediately(): void
    {
        $this->givePermission($this->userA, 'HRMS-StaffManagement');
        $this->givePermission($this->userA, 'HRMS-Approval-L2');

        $offboarded = $this->tenantContext()->runAs($this->tenantA->id, fn () => User::factory()->for($this->tenantA)->create([
            'status' => 'offboarded',
            'lefted_at' => now(),
            'left_reason' => 'resigned',
        ]));

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/status-update', [
            'id' => $offboarded->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);

        $this->tenantContext()->runAs($this->tenantA->id, function () use ($offboarded) {
            $offboarded->refresh();
            $this->assertSame('active', $offboarded->status);
            $this->assertNull($offboarded->lefted_at);
        });
    }

    public function test_clear_device_id_is_idempotent_when_already_empty(): void
    {
        $this->givePermission($this->userA, 'HRMS-StaffManagement');

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/clear-device-id', [
            'id' => $this->userA->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Device ID is already empty.');
    }

    public function test_staff_list_is_scoped_to_the_viewers_selected_branches(): void
    {
        $otherBranch = $this->tenantContext()->runAs(
            $this->tenantA->id,
            fn () => Branch::factory()->for($this->tenantA)->create()
        );

        $this->tenantContext()->runAs($this->tenantA->id, function () use ($otherBranch) {
            $staffOnOtherBranch = User::factory()->for($this->tenantA)->create(['status' => 'active']);
            UserBranch::create(['user_id' => $staffOnOtherBranch->id, 'branch_id' => $otherBranch->id, 'status' => true]);
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/list');

        $response->assertOk();
        // userA only selected $this->branchA (see setUp, where userA is
        // themself assigned to it) — the other branch's staff member is
        // excluded, but userA's own record (on the selected branch) appears.
        $data = collect($response->json('data'));
        $this->assertCount(1, $data);
        $this->assertSame($this->userA->id, $data->first()['id']);
    }
}
