<?php

namespace Tests\Feature\Hr;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    public function test_it_lists_team_heads_with_member_counts(): void
    {
        $this->tenantContext()->runAs($this->tenantA->id, function () {
            $head = User::factory()->for($this->tenantA)->create([
                'status' => 'active',
                'team_name' => 'Platform Team',
            ]);
            User::factory()->for($this->tenantA)->count(2)->create([
                'status' => 'active',
                'hierarchy_parent_id' => $head->id,
            ]);
        });

        $response = $this->actingAs($this->userA, 'sanctum')->getJson('/api/teams/list');

        $response->assertOk();
        $response->assertJsonPath('data.0.team_name', 'Platform Team');
        $response->assertJsonPath('data.0.member_count', 2);
    }

    public function test_it_returns_full_member_list_for_a_team(): void
    {
        $head = $this->tenantContext()->runAs($this->tenantA->id, function () {
            $head = User::factory()->for($this->tenantA)->create([
                'status' => 'active',
                'team_name' => 'Support Team',
            ]);
            User::factory()->for($this->tenantA)->create([
                'status' => 'active',
                'hierarchy_parent_id' => $head->id,
            ]);

            return $head;
        });

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/teams/members', [
            'id' => $head->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.team_name', 'Support Team');
        $response->assertJsonCount(1, 'data.members');
    }

    public function test_it_cannot_view_a_team_belonging_to_another_tenant(): void
    {
        $headB = $this->tenantContext()->runAs(
            $this->tenantB->id,
            fn () => User::factory()->for($this->tenantB)->create(['status' => 'active', 'team_name' => 'Other Team'])
        );

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/teams/members', [
            'id' => $headB->id,
        ]);

        $response->assertUnprocessable();
    }
}
