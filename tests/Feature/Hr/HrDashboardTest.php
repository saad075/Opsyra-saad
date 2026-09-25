<?php

namespace Tests\Feature\Hr;

use App\Models\User;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\StaffOnboardingRequest;
use App\Modules\Hr\Models\UserBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class HrDashboardTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-15');
        $this->seedTwoTenants();
        Permission::findOrCreate('HRMS-StaffManagement', 'sanctum');

        $this->tenantContext()->runAs($this->tenantA->id, function () {
            $this->userA->forceFill(['status' => 'active', 'joined_at' => '2025-01-01'])->save();
            $this->userA->givePermissionTo('HRMS-StaffManagement');

            $head = Branch::create(['name' => 'Head office', 'status' => true]);
            $north = Branch::create(['name' => 'North', 'status' => true]);

            $make = fn (array $attrs, ?Branch $branch = null) => tap(User::factory()->for($this->tenantA)->create($attrs + ['designation' => 'Engineer']),
                fn ($u) => $branch ? UserBranch::create(['user_id' => $u->id, 'branch_id' => $branch->id, 'status' => true]) : null);

            $make(['status' => 'active', 'joined_at' => '2026-09-03'], $head);        // joined this month
            $make(['status' => 'active', 'joined_at' => '2024-05-01'], $head);
            $make(['status' => 'active', 'joined_at' => '2024-05-01'], $north);
            $make(['status' => 'pending', 'joined_at' => '2026-09-25']);             // starting soon
            $make(['status' => 'offboarded', 'joined_at' => '2023-01-01', 'lefted_at' => '2026-09-05']); // left this month
            $make(['status' => 'active', 'joined_at' => '2023-01-01', 'lefted_at' => '2026-10-01']);     // leaving soon

            $candidate = $make(['status' => 'pending_approval', 'joined_at' => '2026-10-01']);
            StaffOnboardingRequest::create(['user_id' => $candidate->id, 'status' => 'pending']);
        });

        // Another company's staff must never be counted.
        $this->tenantContext()->runAs($this->tenantB->id, fn () => User::factory()->for($this->tenantB)->count(5)->create(['status' => 'active', 'joined_at' => '2026-09-02']));
    }

    public function test_the_hr_dashboard_counts_this_companys_people_only(): void
    {
        $data = $this->actingAs($this->userA, 'sanctum')->getJson('/api/hr/dashboard')->assertOk()->json('data');

        $this->assertSame(5, $data['headcount']['active'], 'userA + 4 active staff; tenant B excluded');
        $this->assertSame(1, $data['headcount']['starting_soon']);
        $this->assertSame(1, $data['headcount']['awaiting_approval']);
        $this->assertSame(1, $data['headcount']['joined_this_month']);
        $this->assertSame(1, $data['headcount']['left_this_month']);
        $this->assertSame(1, $data['approvals']['onboarding']);

        $this->assertSame([['label' => 'Head office', 'value' => 2], ['label' => 'North', 'value' => 1]], $data['by_branch']);
        $this->assertCount(1, $data['upcoming_joiners']);
        $this->assertCount(1, $data['upcoming_leavers']);
        $this->assertCount(6, $data['trend']);
        $this->assertSame(5, end($data['trend'])['value'], 'This month’s headcount matches today’s active staff.');
    }

    public function test_staff_without_hr_permission_cannot_see_it(): void
    {
        $colleague = $this->tenantContext()->runAs($this->tenantA->id, fn () => User::factory()->for($this->tenantA)->create(['status' => 'active']));

        $this->actingAs($colleague, 'sanctum')->getJson('/api/hr/dashboard')->assertForbidden();
    }
}
