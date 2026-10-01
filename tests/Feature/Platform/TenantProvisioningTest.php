<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Modules\Directory\Models\ClientDirectoryEntry;
use App\Modules\Hr\Models\ChecklistTemplate;
use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Payroll\Models\PayComponent;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Platform\Services\TenantProvisioner;
use App\Modules\Rbac\Models\Role;
use App\Modules\Rbac\Support\PermissionCatalogue;
use App\Modules\Setup\Models\TenantSetting;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TenantProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function provision(array $overrides = []): array
    {
        return app(TenantProvisioner::class)->provision($overrides + [
            'name' => 'Acme Ltd',
            'client_code' => 'acme',
            'admin_name' => 'Ada Admin',
            'admin_email' => 'ada@acme.test',
        ]);
    }

    public function test_every_permission_the_code_checks_is_in_the_catalogue_and_installed(): void
    {
        $used = collect(File::allFiles(app_path()))
            ->flatMap(fn ($file) => preg_match_all("/'(HRMS-[A-Za-z0-9-]+)'/", $file->getContents(), $m) ? $m[1] : [])
            ->unique();

        foreach ($used as $name) {
            $this->assertArrayHasKey($name, PermissionCatalogue::PERMISSIONS, "{$name} is checked in code but missing from PermissionCatalogue.");
            $this->assertTrue(Permission::where('name', $name)->where('guard_name', 'sanctum')->exists(), "{$name} was not installed by the migration.");
        }
    }

    public function test_a_new_company_gets_its_defaults_and_an_administrator_who_can_start_setup(): void
    {
        ['tenant' => $tenant, 'admin' => $admin, 'password' => $password] = $this->provision();

        $this->assertSame(16, strlen($password));
        $this->assertTrue(ClientDirectoryEntry::where('client_code', 'acme')->exists());

        app(TenantContext::class)->runAs($tenant->id, function () use ($admin) {
            $this->assertSame('Acme Ltd', TenantSetting::get('company_name'));
            $this->assertSame(5, Role::count());
            $this->assertTrue($admin->hasRole('Administrator'));
            $this->assertGreaterThan(0, DepartureReason::count());
            $this->assertNotNull(PayComponent::basic());
            $this->assertNotNull(ChecklistTemplate::defaultFor('onboarding'));

            foreach (array_keys(PermissionCatalogue::PERMISSIONS) as $permission) {
                $this->assertTrue($admin->can($permission), "The first administrator should hold {$permission}.");
            }
        });

        // First they must replace the temporary password (see AuthTest)…
        $this->actingAs($admin, 'sanctum')->getJson('/api/setup-wizard/status')
            ->assertForbidden()
            ->assertJsonPath('password_change_required', true);

        // …after which the wizard opens. The name is pre-filled, but the
        // address and timezone still need entering.
        app(TenantContext::class)->runAs($admin->tenant_id, fn () => $admin->forceFill(['must_change_password' => false])->save());

        $this->actingAs($admin, 'sanctum')->getJson('/api/setup-wizard/status')
            ->assertOk()
            ->assertJsonPath('data.next_step', 'company_details');
    }

    public function test_a_company_from_another_country_is_refused_on_this_deployment(): void
    {
        config(['app.country' => 'us']);

        $this->expectException(InvalidArgumentException::class);
        $this->provision(['country' => 'pk']);
    }

    public function test_a_client_code_can_only_be_used_once(): void
    {
        $this->provision();

        $this->expectException(InvalidArgumentException::class);
        $this->provision(['name' => 'Other', 'admin_email' => 'x@other.test']);
    }

    public function test_an_email_already_used_by_another_company_cannot_become_a_new_admin(): void
    {
        $this->provision();

        $this->expectException(InvalidArgumentException::class);
        $this->provision(['name' => 'Other', 'client_code' => 'other', 'admin_email' => 'ada@acme.test']);
    }

    public function test_the_operator_command_creates_a_company_and_shows_the_password_once(): void
    {
        $this->artisan('tenants:create', [
            'name' => 'Beta Co',
            '--code' => 'beta',
            '--admin-name' => 'Bo Admin',
            '--admin-email' => 'bo@beta.test',
        ])
            ->expectsOutputToContain('Temporary password:')
            ->assertSuccessful();

        $this->assertSame(1, $this->countUsersIn('beta'));
    }

    public function test_the_operator_command_rejects_bad_input(): void
    {
        $this->artisan('tenants:create', ['name' => 'Gamma', '--code' => 'Gamma Co!', '--admin-name' => 'G', '--admin-email' => 'not-an-email'])
            ->assertFailed();
    }

    /**
     * Users are RLS-protected, so counting another tenant's rows needs its context.
     */
    private function countUsersIn(string $clientCode): int
    {
        $tenantId = Tenant::where('client_code', $clientCode)->value('id');

        return app(TenantContext::class)->runAs($tenantId, fn () => User::count());
    }
}
