<?php

namespace Tests\Fixtures;

use App\Modules\Tenancy\Concerns\TenantAwareJob;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Test-only fixture proving TenantAwareJob actually restores tenant context
 * on the worker side, not just that the trait compiles.
 */
class RecordsCurrentTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    public static ?int $observedTenantId = null;

    public function __construct()
    {
        $this->captureTenantContext();
    }

    public function handle(): void
    {
        $tenant = app(TenantContext::class);

        static::$observedTenantId = $tenant->check() ? $tenant->id() : null;
    }
}
