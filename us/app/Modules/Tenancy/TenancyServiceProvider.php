<?php

namespace App\Modules\Tenancy;

use App\Modules\Tenancy\Http\Middleware\ResolveTenantFromUser;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the 'tenant' middleware alias (App\Modules\Tenancy\Http\Middleware
 * \ResolveTenantFromUser) — it's appended to the api middleware group in
 * bootstrap/app.php rather than pushed globally here, so that's the one place
 * to look to confirm every API request resolves a tenant.
 */
class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        $this->app->make('router')->aliasMiddleware('tenant', ResolveTenantFromUser::class);
    }
}
