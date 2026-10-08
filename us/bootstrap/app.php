<?php

use App\Modules\Auth\Http\Middleware\EnsureAccountIsUsable;
use App\Modules\Platform\Http\Middleware\ProtectDemoCompany;
use App\Modules\Platform\Support\MailHealth;
use App\Modules\Tenancy\Http\Middleware\ResolveTenantFromUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Every API request resolves its tenant from the authenticated user
        // (never a client-supplied header) and sets Postgres's app.tenant_id
        // for RLS — see App\Modules\Tenancy\Http\Middleware\ResolveTenantFromUser.
        $middleware->appendToGroup('api', ResolveTenantFromUser::class);

        // Then refuse inactive accounts, and hold anyone with a password they
        // didn't choose at the change-password screen.
        $middleware->appendToGroup('api', EnsureAccountIsUsable::class);

        // The shared public demo company: nothing that could lock the next
        // visitor out (see ProtectDemoCompany).
        $middleware->appendToGroup('api', ProtectDemoCompany::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Let Opsyra Central see when email stops going out (MailHealth).
        // Still logged as usual.
        $exceptions->report(function (TransportExceptionInterface $e) {
            MailHealth::recordFailure($e);
        });
    })->create();
