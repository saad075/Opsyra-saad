<?php

namespace App\Modules\Platform\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\AdminInvitation;
use App\Modules\Platform\Services\TenantProvisioner;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Called only by Opsyra Central (VerifyCentralSignature): creates a
 * company that signed up for a trial on the website. The first
 * administrator gets a "set your password" email from here, so Central
 * never sees a password.
 */
class TenantController extends Controller
{
    public function store(Request $request, TenantProvisioner $provisioner, AdminInvitation $invitation)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client_code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'country' => ['required', 'string', 'size:2'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'trial_ends_at' => ['nullable', 'date'],
            'plan_code' => ['nullable', 'string', 'max:50'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:50'],
        ]);

        try {
            ['tenant' => $tenant, 'admin' => $admin] = $provisioner->provision($validated + ['status' => 'trial']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 409);
        }

        $tenant->update([
            'trial_ends_at' => $validated['trial_ends_at'] ?? null,
            'plan_code' => $validated['plan_code'] ?? null,
            'features' => $validated['features'] ?? null,
        ]);

        $invitation->send($tenant->fresh(), $admin);

        return response()->json([
            'status' => true,
            'message' => 'Company created. The administrator has been emailed.',
            'data' => ['client_code' => $tenant->client_code, 'tenant_id' => $tenant->id],
        ], 201);
    }
}
