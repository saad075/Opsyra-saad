<?php

namespace Tests\Support;

use App\Models\User;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;

/**
 * Base fixture for every cross-tenant isolation test: two real tenants, each
 * with a real user, created through the same RLS policies production traffic
 * goes through (see the note on tenantContext()->runAs() below — a tenant's
 * own rows can only be inserted while that tenant's Postgres session context
 * is active, by design).
 */
trait SeedsTwoTenants
{
    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected User $userA;

    protected User $userB;

    protected function seedTwoTenants(): void
    {
        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();

        // RLS's WITH CHECK means a row can only be inserted while the
        // session's app.tenant_id matches the row's own tenant_id — so
        // seeding tenant A's user requires actually holding tenant A's
        // context, not just passing tenant_id as an attribute.
        $this->userA = $this->tenantContext()->runAs(
            $this->tenantA->id,
            fn () => User::factory()->for($this->tenantA)->create()
        );

        $this->userB = $this->tenantContext()->runAs(
            $this->tenantB->id,
            fn () => User::factory()->for($this->tenantB)->create()
        );

        $this->tenantContext()->clear();
    }

    protected function tenantContext(): TenantContext
    {
        return app(TenantContext::class);
    }

    /**
     * Runs $callback with the app- and DB-layer context both set to $user's
     * tenant, the same as ResolveTenantFromUser does for a real authenticated
     * request — without going through an actual HTTP request/response cycle.
     */
    protected function asTenantOf(User $user, \Closure $callback): mixed
    {
        return $this->tenantContext()->runAs($user->tenant_id, $callback);
    }
}
