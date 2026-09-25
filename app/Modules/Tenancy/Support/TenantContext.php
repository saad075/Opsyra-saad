<?php

namespace App\Modules\Tenancy\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Holds the current request's resolved tenant. Bound as a singleton so every
 * part of the app (global scope, cache keys, storage paths, queued jobs) reads
 * the same tenant for the lifetime of the request/job.
 */
class TenantContext
{
    protected ?int $tenantId = null;

    /**
     * Escape hatch for the "move a large tenant to its own database" scenario:
     * null means "use the country deployment's default shared connection."
     */
    protected ?string $connection = null;

    public function set(int $tenantId, ?string $connection = null): void
    {
        $this->tenantId = $tenantId;
        $this->connection = $connection;

        DB::statement('SET app.tenant_id = '.$tenantId);
    }

    public function clear(): void
    {
        $this->tenantId = null;
        $this->connection = null;

        DB::statement('RESET app.tenant_id');
    }

    public function check(): bool
    {
        return $this->tenantId !== null;
    }

    public function id(): int
    {
        if ($this->tenantId === null) {
            throw new RuntimeException('No tenant is set on the current request.');
        }

        return $this->tenantId;
    }

    public function connectionName(): string
    {
        return $this->connection ?? config('database.default');
    }

    public function cacheKey(string $key): string
    {
        return "tenant_{$this->id()}_{$key}";
    }

    public function storagePath(string $path): string
    {
        return 'tenants/'.$this->id().'/'.ltrim($path, '/');
    }

    /**
     * Explicit, grep-able escape hatch for code that legitimately needs to act
     * as a different tenant than the one resolved for this request — e.g. a
     * super-admin impersonation action, or a cross-tenant scheduled job.
     * Restores the previous tenant (or clears back to none) afterwards.
     */
    public function runAs(int $tenantId, Closure $callback): mixed
    {
        $previousId = $this->tenantId;
        $previousConnection = $this->connection;

        $this->set($tenantId);

        try {
            return $callback();
        } finally {
            if ($previousId === null) {
                $this->clear();
            } else {
                $this->set($previousId, $previousConnection);
            }
        }
    }
}
