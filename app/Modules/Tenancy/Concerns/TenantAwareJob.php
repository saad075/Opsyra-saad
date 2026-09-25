<?php

namespace App\Modules\Tenancy\Concerns;

use App\Modules\Tenancy\Jobs\Middleware\WithTenantContext;
use App\Modules\Tenancy\Support\TenantContext;

/**
 * Add to every ShouldQueue job that touches tenant-scoped data, and call
 * $this->captureTenantContext() as the last line of the job's own
 * constructor. That captures the dispatching request's tenant while it's
 * still available, and the middleware() hook below restores it on the worker
 * before handle() runs — so RLS and BelongsToTenant apply on the worker
 * exactly as they did when the job was dispatched, even though the worker is
 * a separate process/request lifecycle.
 *
 * A job that legitimately spans tenants (e.g. a nightly cross-tenant report)
 * should not use this trait — that's the explicit, grep-able signal that it
 * needs Tenant::runAs() per tenant inside its own handle() instead.
 */
trait TenantAwareJob
{
    public ?int $tenantId = null;

    public function captureTenantContext(): void
    {
        if (app(TenantContext::class)->check()) {
            $this->tenantId = app(TenantContext::class)->id();
        }
    }

    public function middleware(): array
    {
        return [new WithTenantContext];
    }
}
