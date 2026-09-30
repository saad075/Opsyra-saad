<?php

namespace App\Modules\Platform\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Services\AdminInvitation;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Platform\Services\TenantProvisioner;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Called only by Opsyra Central (VerifyCentralSignature): creates a
 * company that signed up for a trial on the website. The first
 * administrator gets a "set your password" email from here, so Central
 * never sees a password.
 *
 * Safe to repeat: if Central asks again for a company that already exists
 * here with the same administrator (its first request got this far but the
 * answer was lost), that's a success and a fresh link is sent — so Central's
 * "Retry setup" can always recover a half-finished sign-up.
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

        $existing = Tenant::where('client_code', $validated['client_code'])->first();
        $existingAdmin = $existing ? $this->findUser($existing, $validated['admin_email']) : null;
        $created = $existingAdmin === null;

        if ($created) {
            try {
                ['tenant' => $tenant, 'admin' => $admin] = $provisioner->provision($validated + ['status' => 'trial']);
            } catch (InvalidArgumentException $e) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 409);
            }
        } else {
            [$tenant, $admin] = [$existing, $existingAdmin];
        }

        $tenant->update([
            'trial_ends_at' => $validated['trial_ends_at'] ?? null,
            'plan_code' => $validated['plan_code'] ?? null,
            'features' => $validated['features'] ?? null,
        ]);

        $invitation->send($tenant, $admin);

        return response()->json([
            'status' => true,
            'message' => $created ? 'Company created. The administrator is being emailed.' : 'Company already existed. A new welcome email is on its way.',
            'data' => ['client_code' => $tenant->client_code, 'tenant_id' => $tenant->id, 'already_existed' => ! $created],
        ], $created ? 201 : 200);
    }

    /** Sends the administrator a fresh welcome link (the old one stops working). */
    public function resendWelcome(Request $request, string $clientCode, AdminInvitation $invitation)
    {
        $email = $request->validate(['admin_email' => ['required', 'email', 'max:255']])['admin_email'];

        $tenant = Tenant::where('client_code', $clientCode)->first();
        $admin = $tenant ? $this->findUser($tenant, $email) : null;

        if (! $admin) {
            return response()->json(['status' => false, 'message' => "No user {$email} at company '{$clientCode}'."], 404);
        }

        $invitation->send($tenant, $admin);

        return response()->json(['status' => true, 'message' => 'A new welcome email is on its way.'], 202);
    }

    private function findUser(Tenant $tenant, string $email): ?User
    {
        return app(TenantContext::class)->runAs($tenant->id, fn () => User::whereRaw('lower(email) = ?', [strtolower($email)])->first());
    }
}
