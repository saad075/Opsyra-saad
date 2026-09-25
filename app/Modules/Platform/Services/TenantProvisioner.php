<?php

namespace App\Modules\Platform\Services;

use App\Models\User;
use App\Modules\Directory\Models\ClientDirectoryEntry;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Rbac\Support\DefaultRoles;
use App\Modules\Setup\Models\TenantSetting;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Creates a company end to end: the tenant (whose `created` event seeds
 * every module's defaults — roles, leaving reasons, checklist templates, pay
 * components), its first Administrator, and its directory entry so the
 * mobile/web apps can find this deployment from the client code.
 *
 * Operator-driven for now (`tenants:create`): there's no super-admin panel
 * or self sign-up yet. Either will call this same service.
 */
class TenantProvisioner
{
    /**
     * @param  array{name: string, client_code: string, country?: string, admin_name: string, admin_email: string, status?: string}  $data
     * @return array{tenant: Tenant, admin: User, password: string}
     */
    public function provision(array $data): array
    {
        $country = strtolower($data['country'] ?? config('app.country'));

        if ($country !== strtolower(config('app.country'))) {
            throw new InvalidArgumentException(
                "This is the '".config('app.country')."' deployment; a '{$country}' company must be created on that country's deployment."
            );
        }

        if (Tenant::withTrashed()->where('client_code', $data['client_code'])->exists()
            || ClientDirectoryEntry::where('client_code', $data['client_code'])->exists()) {
            throw new InvalidArgumentException("Client code '{$data['client_code']}' is already taken.");
        }

        $password = Str::password(16);

        return DB::transaction(function () use ($data, $country, $password) {
            $tenant = Tenant::create([
                'client_code' => $data['client_code'],
                'name' => $data['name'],
                'country' => $country,
                'status' => $data['status'] ?? 'trial',
            ]);

            $admin = app(TenantContext::class)->runAs($tenant->id, function () use ($data, $password) {
                TenantSetting::set('company_name', $data['name']);

                $admin = User::create([
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => $password,
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'joined_at' => today(),
                ]);

                // They were handed a temporary password.
                $admin->forceFill(['must_change_password' => true])->save();

                $admin->assignRole(DefaultRoles::ADMINISTRATOR);

                return $admin;
            });

            ClientDirectoryEntry::create([
                'client_code' => $data['client_code'],
                'country' => $country,
                'endpoint_url' => config('app.url'),
                'is_active' => true,
            ]);

            return ['tenant' => $tenant, 'admin' => $admin, 'password' => $password];
        });
    }
}
