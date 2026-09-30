<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Platform\Models\OwnershipTransfer;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Platform\Services\TenantProvisioner;
use App\Modules\Rbac\Models\Role;
use App\Modules\Rbac\Support\DefaultRoles;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The company account owner: a company can't lock itself out by removing
 * its only administrator, offboarding them, or gutting the Administrator
 * role; ownership only moves by an audited transfer.
 */
class CompanyOwnerTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Owner-Pass-2026';

    private Tenant $tenant;

    private User $owner;

    private User $otherAdmin;

    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.country' => 'us']);
        [$this->tenant, $this->owner] = $this->company('acme', 'owner@acme.test');
        $this->otherAdmin = $this->person($this->tenant, 'second@acme.test', DefaultRoles::ADMINISTRATOR);
        $this->worker = $this->person($this->tenant, 'worker@acme.test');
    }

    /** @return array{Tenant, User} */
    private function company(string $code, string $email): array
    {
        ['tenant' => $tenant, 'admin' => $admin] = app(TenantProvisioner::class)->provision([
            'name' => ucfirst($code).' Ltd', 'client_code' => $code, 'country' => 'us',
            'admin_name' => 'Olive Owner', 'admin_email' => $email,
        ]);

        return [$tenant->fresh(), $this->runAs($tenant, function () use ($admin) {
            $admin->forceFill(['must_change_password' => false, 'password' => self::PASSWORD])->save();

            return $admin->fresh();
        })];
    }

    private function person(Tenant $tenant, string $email, ?string $role = null): User
    {
        return $this->runAs($tenant, function () use ($tenant, $email, $role) {
            $user = User::factory()->for($tenant)->create(['email' => $email, 'status' => 'active']);

            if ($role) {
                $user->assignRole($role);
            }

            return $user;
        });
    }

    private function runAs(Tenant $tenant, \Closure $callback): mixed
    {
        return app(TenantContext::class)->runAs($tenant->id, $callback);
    }

    private function administratorRole(): Role
    {
        return $this->runAs($this->tenant, fn () => Role::where('name', DefaultRoles::ADMINISTRATOR)->firstOrFail());
    }

    private function updateRole(User $actor, User $target, ?int $roleId)
    {
        $branch = $this->runAs($this->tenant, fn () => Branch::firstOrCreate(['name' => 'Main']));

        return $this->actingAs($actor, 'sanctum')->postJson('/api/staff/update', [
            'user_id' => $target->id, 'name' => $target->name, 'email' => $target->email, 'phone' => '555-0100',
            'is_remote' => false, 'dob' => '1990-01-01', 'designation' => 'Staff', 'joined_at' => '2026-01-01',
            'branches' => [$branch->id], 'role_id' => $roleId,
        ]);
    }

    private function offboard(User $actor, User $target)
    {
        $reason = $this->runAs($this->tenant, fn () => DepartureReason::where('is_active', true)->where('requires_note', false)->firstOrFail());

        return $this->actingAs($actor, 'sanctum')->postJson('/api/staff/offboard', [
            'id' => $target->id, 'last_working_day' => now()->addWeek()->toDateString(), 'departure_reason_id' => $reason->id,
        ]);
    }

    public function test_the_signup_administrator_is_the_account_owner(): void
    {
        $this->assertSame($this->owner->id, $this->tenant->owner_user_id);

        $this->actingAs($this->owner, 'sanctum')->getJson('/api/auth/me')->assertJsonPath('data.is_owner', true);
        $this->actingAs($this->otherAdmin, 'sanctum')->getJson('/api/auth/me')->assertJsonPath('data.is_owner', false);
    }

    public function test_the_owner_cannot_be_offboarded(): void
    {
        $this->offboard($this->otherAdmin, $this->owner)->assertStatus(409)->assertJsonPath('message', fn ($m) => str_contains($m, 'account owner'));

        // Anyone else still can.
        $this->offboard($this->owner, $this->worker)->assertSuccessful();
    }

    public function test_the_owner_always_keeps_the_administrator_role(): void
    {
        $hrManager = $this->runAs($this->tenant, fn () => Role::where('name', 'HR Manager')->firstOrFail());

        $this->updateRole($this->otherAdmin, $this->owner, $hrManager->id)->assertStatus(409);
        $this->updateRole($this->otherAdmin, $this->owner, null)->assertStatus(409);
        $this->assertTrue($this->runAs($this->tenant, fn () => $this->owner->fresh()->hasRole(DefaultRoles::ADMINISTRATOR)));

        // Another administrator can be moved to a different role.
        $this->updateRole($this->owner, $this->otherAdmin, $hrManager->id)->assertOk();
        $this->assertFalse($this->runAs($this->tenant, fn () => $this->otherAdmin->fresh()->hasRole(DefaultRoles::ADMINISTRATOR)));
    }

    public function test_the_administrator_role_cannot_be_changed_or_deleted(): void
    {
        $role = $this->administratorRole();

        $this->actingAs($this->owner, 'sanctum')->postJson('/api/roles/update', ['id' => $role->id, 'name' => 'Admin', 'permissions' => ['HRMS-StaffManagement']])->assertStatus(409);
        $this->actingAs($this->owner, 'sanctum')->postJson('/api/roles/delete', ['id' => $role->id])->assertStatus(409);

        $role = $this->administratorRole();
        $this->assertSame(DefaultRoles::ADMINISTRATOR, $role->name);
        $this->assertCount(count(DefaultRoles::definitions()[DefaultRoles::ADMINISTRATOR]), $this->runAs($this->tenant, fn () => $role->permissions));

        $list = $this->actingAs($this->owner, 'sanctum')->getJson('/api/roles/list')->assertOk();
        $this->assertTrue(collect($list->json('data'))->firstWhere('name', DefaultRoles::ADMINISTRATOR)['is_locked']);
    }

    public function test_the_owner_hands_ownership_on_and_it_is_recorded(): void
    {
        $this->actingAs($this->owner, 'sanctum')->postJson('/api/company/owner/transfer', [
            'user_id' => $this->worker->id, 'password' => self::PASSWORD, 'reason' => 'Olive is retiring',
        ])->assertOk();

        $this->assertSame($this->worker->id, $this->tenant->fresh()->owner_user_id);
        // The new owner is made an administrator; the old one keeps theirs.
        $this->assertTrue($this->runAs($this->tenant, fn () => $this->worker->fresh()->hasRole(DefaultRoles::ADMINISTRATOR)));
        $this->assertTrue($this->runAs($this->tenant, fn () => $this->owner->fresh()->hasRole(DefaultRoles::ADMINISTRATOR)));

        $show = $this->actingAs($this->otherAdmin, 'sanctum')->getJson('/api/company/owner/show')->assertOk();
        $show->assertJsonPath('data.owner.id', $this->worker->id)->assertJsonPath('data.is_me', false);
        $this->assertSame(
            ['from' => 'Olive Owner', 'to' => $this->worker->name, 'by' => 'Olive Owner', 'reason' => 'Olive is retiring'],
            collect($show->json('data.history.0'))->only(['from', 'to', 'by', 'reason'])->all(),
        );

        // The former owner can now be offboarded like anyone else.
        $this->offboard($this->worker, $this->owner)->assertSuccessful();
    }

    public function test_only_the_owner_can_transfer_and_only_with_their_password(): void
    {
        $this->actingAs($this->otherAdmin, 'sanctum')->postJson('/api/company/owner/transfer', [
            'user_id' => $this->otherAdmin->id, 'password' => 'password',
        ])->assertForbidden();

        $this->actingAs($this->owner, 'sanctum')->postJson('/api/company/owner/transfer', [
            'user_id' => $this->worker->id, 'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertSame($this->owner->id, $this->tenant->fresh()->owner_user_id);
    }

    public function test_ownership_cannot_go_to_someone_inactive_leaving_or_in_another_company(): void
    {
        $inactive = $this->person($this->tenant, 'gone@acme.test');
        $this->runAs($this->tenant, fn () => $inactive->forceFill(['status' => 'offboarded'])->save());

        $this->actingAs($this->owner, 'sanctum')->postJson('/api/company/owner/transfer', ['user_id' => $inactive->id, 'password' => self::PASSWORD])->assertStatus(409);

        $this->offboard($this->owner, $this->worker)->assertSuccessful();
        $this->actingAs($this->owner, 'sanctum')->postJson('/api/company/owner/transfer', ['user_id' => $this->worker->id, 'password' => self::PASSWORD])->assertStatus(409);

        [, $outsider] = $this->company('globex', 'owner@globex.test');
        $this->actingAs($this->owner, 'sanctum')->postJson('/api/company/owner/transfer', ['user_id' => $outsider->id, 'password' => self::PASSWORD])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');

        $this->assertSame($this->owner->id, $this->tenant->fresh()->owner_user_id);
        $this->assertSame(0, $this->runAs($this->tenant, fn () => OwnershipTransfer::count()));
    }

    public function test_staff_without_company_settings_cannot_see_the_owner_page(): void
    {
        $this->actingAs($this->worker, 'sanctum')->getJson('/api/company/owner/show')->assertForbidden();
    }

    public function test_the_staff_profile_says_who_the_owner_is(): void
    {
        $this->actingAs($this->otherAdmin, 'sanctum')->postJson('/api/staff/show', ['id' => $this->owner->id])->assertJsonPath('data.is_owner', true);
        $this->actingAs($this->otherAdmin, 'sanctum')->postJson('/api/staff/show', ['id' => $this->worker->id])->assertJsonPath('data.is_owner', false);
    }

    public function test_the_demo_company_ownership_cannot_be_changed(): void
    {
        $this->tenant->forceFill(['is_demo' => true])->save();

        $this->actingAs($this->owner, 'sanctum')->postJson('/api/company/owner/transfer', ['user_id' => $this->worker->id, 'password' => self::PASSWORD])
            ->assertForbidden()->assertJsonPath('demo_locked', true);
    }
}
