<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Platform\Services\TenantProvisioner;
use App\Modules\Rbac\Models\Role;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression (2026-09-30): Spatie's single permissions cache only held the
 * roles of whichever company built it (roles are behind RLS), so every
 * other company's administrators were refused everything.
 */
class PermissionCacheIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{Tenant, User} */
    private function company(string $code): array
    {
        config(['app.country' => 'us']);
        ['tenant' => $tenant, 'admin' => $admin] = app(TenantProvisioner::class)->provision([
            'name' => $code, 'client_code' => $code, 'country' => 'us', 'admin_name' => 'Admin', 'admin_email' => "admin@{$code}.test",
        ]);

        return [$tenant, $admin];
    }

    private function can(Tenant $tenant, User $user, string $permission): bool
    {
        return app(TenantContext::class)->runAs($tenant->id, fn () => $user->fresh()->can($permission));
    }

    public function test_each_company_gets_its_own_permission_lookup(): void
    {
        [$a, $adminA] = $this->company('alpha');
        [$b, $adminB] = $this->company('bravo');
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        // Company A's request builds the cache first…
        $this->assertTrue($this->can($a, $adminA, 'HRMS-StaffManagement'));

        // …then company B, in the same process (a queue worker) and in a
        // new request reading the shared cache.
        $this->assertTrue($this->can($b, $adminB, 'HRMS-StaffManagement'));
        $registrar->clearPermissionsCollection();
        $this->assertTrue($this->can($b, $adminB, 'HRMS-StaffManagement'));
        $this->assertTrue($this->can($a, $adminA, 'HRMS-StaffManagement'));
    }

    public function test_a_role_change_reaches_every_company_cache(): void
    {
        [$a] = $this->company('alpha');
        $hr = app(TenantContext::class)->runAs($a->id, function () use ($a) {
            $user = User::factory()->for($a)->create(['status' => 'active']);
            $user->assignRole('HR Manager');

            return $user;
        });

        $this->assertTrue($this->can($a, $hr, 'HRMS-StaffManagement'));

        app(TenantContext::class)->runAs($a->id, fn () => Role::where('name', 'HR Manager')->first()->revokePermissionTo('HRMS-StaffManagement'));
        app(PermissionRegistrar::class)->clearPermissionsCollection();

        $this->assertFalse($this->can($a, $hr, 'HRMS-StaffManagement'));
    }
}
