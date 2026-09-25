<?php

namespace App\Modules\Tenancy\Jobs\Middleware;

use App\Modules\Tenancy\Support\TenantContext;
use Closure;

class WithTenantContext
{
    public function handle(mixed $job, Closure $next): void
    {
        $tenantId = $job->tenantId ?? null;

        if ($tenantId === null) {
            $next($job);

            return;
        }

        app(TenantContext::class)->runAs($tenantId, fn () => $next($job));
    }
}
