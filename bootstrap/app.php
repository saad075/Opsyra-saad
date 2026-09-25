<?php

use App\Modules\Tenancy\Http\Middleware\ResolveTenantFromUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
