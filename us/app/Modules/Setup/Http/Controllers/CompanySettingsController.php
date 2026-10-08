<?php

namespace App\Modules\Setup\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Support\Money;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Setup\Models\TenantSetting;
use Illuminate\Http\Request;

/**
 * The company profile (the wizard's first step). Replaces SLMS360's
 * org_name column on the client's users row plus getSetting() rows. The
 * country comes from the tenant's deployment and isn't editable here.
 */
class CompanySettingsController extends Controller
{
    public function show(Request $request)
    {
        $tenant = Tenant::findOrFail($request->user()->tenant_id);
        $settings = TenantSetting::many(TenantSetting::COMPANY_KEYS);

        return response()->json([
            'status' => true,
            'message' => 'Company settings',
            // array_merge, not +: + keeps the left side's null for unset keys.
            'data' => array_merge($settings, [
                'company_name' => $settings['company_name'] ?? $tenant->name,
                'country' => $tenant->country,
                'currency' => $settings['currency'] ?? Money::currencyForCountry($tenant->country),
            ]),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('run-setup');

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', 'timezone:all'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        foreach ($validated as $key => $value) {
            TenantSetting::set($key, $key === 'currency' && $value ? strtoupper($value) : $value);
        }

        return response()->json(['status' => true, 'message' => 'Company settings saved.']);
    }
}
