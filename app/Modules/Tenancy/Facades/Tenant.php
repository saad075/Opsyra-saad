<?php

namespace App\Modules\Tenancy\Facades;

use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void set(int $tenantId, ?string $connection = null)
 * @method static void clear()
 * @method static bool check()
 * @method static int id()
 * @method static string connectionName()
 * @method static string cacheKey(string $key)
 * @method static string storagePath(string $path)
 * @method static mixed runAs(int $tenantId, \Closure $callback)
 *
 * @see TenantContext
 */
class Tenant extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TenantContext::class;
    }
}
