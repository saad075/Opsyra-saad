<?php

namespace App\Modules\Platform\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Models\TenantAccessToken;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Called only by Opsyra Central (VerifyCentralSignature).
 *
 * - update(): Central is the source of truth for subscriptions; this
 *   applies its decision to the local enforcement copy on the tenant row
 *   (SLMS360's subscription bridge, per company rather than per install).
 * - usage(): aggregate counts only — how many active staff each company
 *   has, for per-employee pricing. No names, no personal data leave the
 *   country (docs/research/subscription-architecture.md).
 */
class SubscriptionController extends Controller
{
    public function update(Request $request, string $clientCode)
    {
        $tenant = Tenant::where('client_code', $clientCode)->firstOrFail();

        $validated = $request->validate([
            // The tenants.status column only knows these four; Central maps
            // its own richer statuses (e.g. past_due → active) before sending.
            'status' => ['required', Rule::in(['trial', 'active', 'suspended', 'cancelled'])],
            'trial_ends_at' => ['nullable', 'date'],
            'plan_code' => ['nullable', 'string', 'max:50'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:50'],
        ]);

        $tenant->update($validated);

        // Suspending or cancelling signs everyone out straight away, rather
        // than waiting for tokens to be refused one request at a time.
        if (in_array($validated['status'], ['suspended', 'cancelled'], true)) {
            TenantAccessToken::where('tenant_id', $tenant->id)->delete();
        }

        return response()->json(['status' => true, 'message' => 'Subscription updated.']);
    }

    public function usage()
    {
        $data = Tenant::query()->get()->map(fn (Tenant $tenant) => [
            'client_code' => $tenant->client_code,
            'active_employees' => app(TenantContext::class)->runAs($tenant->id, fn () => User::where('status', 'active')->count()),
        ]);

        return response()->json(['status' => true, 'data' => $data]);
    }
}
