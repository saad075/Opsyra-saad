<?php

namespace App\Modules\Tenancy\Concerns;

use App\Modules\Tenancy\Scopes\TenantScope;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Add to every tenant-scoped model. Applies the TenantScope global scope
 * (filters every query by tenant_id) and auto-fills tenant_id on create.
 *
 * Opting out is explicit and grep-able: `Model::withoutTenantScope()->...`.
 * This only bypasses the app-layer filter — Postgres RLS (see TenantRls)
 * still applies unless the caller also holds a tenant context via
 * Tenant::runAs(), which is its own explicit, grep-able escape hatch.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (is_null($model->tenant_id) && app(TenantContext::class)->check()) {
                $model->tenant_id = app(TenantContext::class)->id();
            }
        });
    }

    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
