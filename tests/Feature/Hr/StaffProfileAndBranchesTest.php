<?php

namespace Tests\Feature\Hr;

use App\Models\User;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\UserBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class StaffProfileAndBranchesTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
        Permission::findOrCreate('HRMS-StaffManagement', 'sanctum');
        $this->inA(fn () => $this->userA->givePermissionTo('HRMS-StaffManagement'));
    }

    private function inA(\Closure $callback): mixed
    {
        return $this->tenantContext()->runAs($this->tenantA->id, $callback);
    }

    public function test_whoever_creates_a_branch_belongs_to_it_and_sees_it(): void
    {
        $id = $this->actingAs($this->userA, 'sanctum')->postJson('/api/branches/create', ['name' => 'Head office'])->assertCreated()->json('data.id');

        $this->actingAs($this->userA, 'sanctum')->getJson('/api/my-branches')
            ->assertOk()
            ->assertJsonPath('data.0', ['id' => $id, 'name' => 'Head office', 'selected' => true]);
    }

    public function test_the_branch_switcher_changes_whose_staff_are_listed(): void
    {
        [$north, $south, $colleague] = $this->inA(function () {
            $north = Branch::create(['name' => 'North', 'status' => true]);
            $south = Branch::create(['name' => 'South', 'status' => true]);
            UserBranch::create(['user_id' => $this->userA->id, 'branch_id' => $north->id, 'status' => true]);
            UserBranch::create(['user_id' => $this->userA->id, 'branch_id' => $south->id, 'status' => true]);

            $colleague = User::factory()->for($this->tenantA)->create(['name' => 'South Sam', 'status' => 'active']);
            UserBranch::create(['user_id' => $colleague->id, 'branch_id' => $south->id, 'status' => true]);

            return [$north, $south, $colleague];
        });

        $names = fn () => collect($this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/list')->json('data'))->pluck('name');

        $this->assertContains('South Sam', $names());

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/my-branches/select', ['branch_ids' => [$north->id]])->assertOk();
        $this->assertNotContains('South Sam', $names());
    }

    public function test_you_cannot_switch_to_a_branch_you_dont_belong_to(): void
    {
        $theirs = $this->inA(fn () => Branch::create(['name' => 'Not mine', 'status' => true]));

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/my-branches/select', ['branch_ids' => [$theirs->id]])
            ->assertUnprocessable();
    }

    public function test_hr_can_open_anyones_profile_and_staff_only_their_own(): void
    {
        [$manager, $report] = $this->inA(function () {
            $manager = User::factory()->for($this->tenantA)->create(['name' => 'Mia Manager', 'status' => 'active']);
            $report = User::factory()->for($this->tenantA)->create(['status' => 'active', 'hierarchy_parent_id' => $manager->id]);

            return [$manager, $report];
        });

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/show', ['id' => $manager->id])
            ->assertOk()
            ->assertJsonPath('data.staff.name', 'Mia Manager')
            ->assertJsonPath('data.direct_reports.0.id', $report->id);

        $this->actingAs($report, 'sanctum')->postJson('/api/staff/show', ['id' => $report->id])
            ->assertOk()
            ->assertJsonPath('data.manager.name', 'Mia Manager');

        $this->actingAs($report, 'sanctum')->postJson('/api/staff/show', ['id' => $manager->id])->assertForbidden();
    }

    public function test_another_companys_profile_is_out_of_reach(): void
    {
        $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/show', ['id' => $this->userB->id])->assertUnprocessable();
    }
}
