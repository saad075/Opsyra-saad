<?php

namespace App\Modules\Rbac\Support;

use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * Spatie keeps one cached list of "which roles hold which permission" for
 * the whole app. Roles are tenant rows behind RLS, so that list only ever
 * held the roles of whichever company's request built it: every other
 * company's administrators were refused everything until the cache expired
 * (found 2026-09-30: Kindstead's owner could open nothing).
 *
 * This keeps one list per company, both in the cache and in memory, and
 * rebuilds it when the tenant context changes (queue workers move between
 * companies). Any role or permission change bumps a version that is part
 * of every key, so all companies' lists are rebuilt on next use.
 */
class TenantPermissionRegistrar extends PermissionRegistrar
{
    /** Which company (and version) the in-memory list belongs to. */
    private ?string $loadedFor = null;

    public function getPermissions(array $params = [], bool $onlyOne = false): Collection
    {
        $context = app(TenantContext::class);
        $scope = $context->check() ? 'tenant'.$context->id() : 'global';

        if ($this->loadedFor === null || ! str_ends_with($this->loadedFor, '.'.$scope)) {
            $this->clearPermissionsCollection();
            $this->loadedFor = 'v'.$this->version().'.'.$scope;
            $this->cacheKey = config('permission.cache.key').'.'.$this->loadedFor;
        }

        return parent::getPermissions($params, $onlyOne);
    }

    public function forgetCachedPermissions()
    {
        $this->cache->forever($this->versionKey(), $this->version() + 1);
        $this->loadedFor = null;

        return parent::forgetCachedPermissions();
    }

    private function version(): int
    {
        return (int) $this->cache->get($this->versionKey(), 1);
    }

    private function versionKey(): string
    {
        return config('permission.cache.key').'.version';
    }
}
