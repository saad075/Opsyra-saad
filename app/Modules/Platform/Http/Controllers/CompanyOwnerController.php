<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Platform\Models\OwnershipTransfer;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Platform\Services\CompanyOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Who the company account owner is, and handing it on. Only the owner can
 * transfer, after re-entering their password; everyone who can see
 * Company details can see who the owner is and the history.
 */
class CompanyOwnerController extends Controller
{
    public function show(Request $request, CompanyOwnership $ownership)
    {
        $user = $request->user();

        abort_unless($user->can('HRMS-CompanySettings') || $ownership->isOwner($user), 403);

        $ownerId = $ownership->ownerId($user->tenant_id);

        return response()->json([
            'status' => true,
            'message' => 'Account owner',
            'data' => [
                'owner' => $ownerId ? User::find($ownerId, ['id', 'name', 'email', 'designation']) : null,
                'is_me' => $ownerId === $user->id,
                'history' => OwnershipTransfer::with(['fromUser:id,name', 'toUser:id,name', 'byUser:id,name'])
                    ->latest('id')
                    ->limit(20)
                    ->get()
                    ->map(fn (OwnershipTransfer $t) => [
                        'id' => $t->id,
                        'from' => $t->fromUser?->name,
                        'to' => $t->toUser?->name,
                        'by' => $t->byUser?->name ?? $t->by_label,
                        'reason' => $t->reason,
                        'at' => $t->created_at,
                    ]),
            ],
        ]);
    }

    public function transfer(Request $request, CompanyOwnership $ownership)
    {
        $user = $request->user();

        abort_unless($ownership->isOwner($user), 403, 'Only the account owner can hand ownership on.');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('tenant_id', $user->tenant_id), Rule::notIn([$user->id])],
            'password' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], ['user_id.not_in' => 'You’re already the account owner.']);

        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Your password is incorrect.']);
        }

        $to = User::findOrFail($validated['user_id']);
        $ownership->transfer(Tenant::findOrFail($user->tenant_id), $to, $user, reason: $validated['reason'] ?? null);

        return response()->json(['status' => true, 'message' => "{$to->name} is now the account owner."]);
    }
}
