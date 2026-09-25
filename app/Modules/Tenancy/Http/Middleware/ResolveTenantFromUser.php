<?php

namespace App\Modules\Tenancy\Http\Middleware;

use App\Modules\Tenancy\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for a request from the authenticated user, never from a
 * client-supplied header/param — a client can't spoof its way into another
 * tenant just by sending a different tenant/client code once logged in. The
 * one place a client-supplied code is trusted is login itself (see the
 * Directory module and each module's own login controller), because there is
 * no authenticated user yet to derive it from.
 *
 * Binds TenantContext and sets Postgres's app.tenant_id for the request, then
 * resets it in terminate() so a reused PHP process (e.g. `php artisan serve`,
 * or Octane) never leaks one request's tenant into the next.
 */
class ResolveTenantFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && isset($user->tenant_id)) {
            app(TenantContext::class)->set($user->tenant_id);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(TenantContext::class)->clear();
    }
}
