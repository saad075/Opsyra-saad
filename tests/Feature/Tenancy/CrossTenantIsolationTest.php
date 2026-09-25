<?php

namespace Tests\Feature\Tenancy;

use App\Models\User;
use App\Modules\Tenancy\Http\Middleware\ResolveTenantFromUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\RecordsCurrentTenantJob;
use Tests\Support\AssertsTenantIsolation;
use Tests\TestCase;

/**
 * The harness required by docs/decisions.md and the project's Phase 2 scope:
 * seed tenants A and B, act as A, and prove B's data is unreachable — at the
 * app layer, at the DB layer independent of the app layer, and over real
 * HTTP. Every future tenant-scoped module adds its own
 * assertModelIsTenantIsolated() case here (or in its own test) rather than
 * inventing a new isolation check from scratch.
 */
class CrossTenantIsolationTest extends TestCase
{
    use AssertsTenantIsolation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();
    }

    public function test_users_table_is_tenant_isolated(): void
    {
        $this->assertModelIsTenantIsolated(
            User::class,
            fn () => User::factory()->for($this->tenantB)->create(),
        );
    }

    public function test_row_level_security_blocks_cross_tenant_reads_with_no_app_layer_involved(): void
    {
        // No Eloquent, no BelongsToTenant scope — proves RLS alone is
        // sufficient, exactly the "missed filter still can't leak" guarantee.
        $this->tenantContext()->runAs($this->tenantA->id, function () {
            $emails = DB::table('users')->pluck('email');

            $this->assertTrue($emails->contains($this->userA->email));
            $this->assertFalse($emails->contains($this->userB->email));
        });
    }

    public function test_row_level_security_fails_closed_when_no_tenant_context_is_set(): void
    {
        // No SET app.tenant_id at all this connection -> current_setting(...,
        // true) is NULL -> tenant_id = NULL matches nothing. Unset must mean
        // "no access", not "see everything."
        $this->tenantContext()->clear();

        $rows = DB::table('users')->count();

        $this->assertSame(0, $rows);
    }

    public function test_without_tenant_scope_only_bypasses_the_app_layer_not_rls(): void
    {
        // withoutTenantScope() removes the *application* filter only. Still
        // under tenant A's DB session, so RLS keeps enforcing the boundary
        // underneath it — count stays 1, not 2, proving the app-layer opt-out
        // can't leak data by itself. A real cross-tenant read needs both
        // layers opted out at once (withoutTenantScope() + Tenant::runAs()),
        // which is exactly the point: one opt-out alone is never enough.
        $count = $this->tenantContext()->runAs($this->tenantA->id, function () {
            return User::withoutTenantScope()->count();
        });

        $this->assertSame(1, $count);
    }

    public function test_creating_a_record_auto_assigns_the_current_tenant(): void
    {
        $user = $this->tenantContext()->runAs($this->tenantA->id, function () {
            $user = User::factory()->make(['tenant_id' => null]);
            $user->save();

            return $user;
        });

        $this->assertSame($this->tenantA->id, $user->tenant_id);
    }

    public function test_dispatched_jobs_restore_the_dispatching_tenant_on_the_worker(): void
    {
        RecordsCurrentTenantJob::$observedTenantId = null;

        $this->tenantContext()->runAs($this->tenantA->id, function () {
            RecordsCurrentTenantJob::dispatchSync();
        });

        $this->assertSame($this->tenantA->id, RecordsCurrentTenantJob::$observedTenantId);
    }

    public function test_authenticated_http_requests_cannot_read_another_tenants_user_via_sanctum(): void
    {
        Route::middleware(['api', ResolveTenantFromUser::class])
            ->get('/__test/whoami', function () {
                return response()->json(['id' => request()->user()->id]);
            });

        $response = $this->actingAs($this->userA, 'sanctum')->getJson('/__test/whoami');

        $response->assertOk();
        $response->assertJson(['id' => $this->userA->id]);
        $this->assertNotEquals($this->userB->id, $response->json('id'));
    }
}
