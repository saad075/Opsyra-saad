<?php

namespace Tests\Unit\Tenancy;

use App\Modules\Tenancy\Support\TenantContext;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    #[Test]
    public function cache_keys_and_storage_paths_are_prefixed_with_the_current_tenant(): void
    {
        $tenant = app(TenantContext::class);

        $tenant->runAs(42, function () use ($tenant) {
            $this->assertSame('tenant_42_dashboard_stats', $tenant->cacheKey('dashboard_stats'));
            $this->assertSame('tenants/42/uploads/id-card.png', $tenant->storagePath('uploads/id-card.png'));
            $this->assertSame('tenants/42/uploads/id-card.png', $tenant->storagePath('/uploads/id-card.png'));
        });
    }

    #[Test]
    public function it_refuses_to_build_a_cache_key_or_storage_path_with_no_tenant_set(): void
    {
        $tenant = app(TenantContext::class);
        $tenant->clear();

        $this->expectException(RuntimeException::class);

        $tenant->cacheKey('dashboard_stats');
    }
}
