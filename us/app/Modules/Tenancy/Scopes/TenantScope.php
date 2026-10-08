<?php

namespace App\Modules\Tenancy\Scopes;

use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * App-layer half of tenant isolation (the other half is Postgres RLS — see
 * App\Modules\Tenancy\Support\TenantRls). A missed filter here still can't
 * leak another tenant's rows, because RLS enforces the same boundary at the
 * database connection level regardless of what Eloquent does.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = app(TenantContext::class);

        if ($tenant->check()) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenant->id());
        }
    }
}
