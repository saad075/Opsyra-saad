<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Models\PasswordResetCode;
use App\Modules\Auth\Notifications\PasswordResetNotification;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * New — SLMS360 had no self-service reset. Tenant-scoped (emails are only
 * unique within a company). forgot() answers the same whether or not the
 * account exists, so it can't be used to discover emails. Tokens are
 * single-use, expire after an hour, and only their hash is stored.
 */
class PasswordResetController extends Controller
{
    private const EXPIRES_MINUTES = 60;

    public function forgot(Request $request)
    {
        $validated = $request->validate([
            'client_code' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email'],
        ]);

        [$tenant, $user] = $this->resolve($validated['client_code'], $validated['email']);

        if ($user && ! in_array($user->status, ['pending_approval', 'offboarded'], true)) {
            app(TenantContext::class)->runAs($tenant->id, function () use ($user, $tenant) {
                $token = Str::random(64);

                // One live reset at a time: asking again replaces the old one.
                PasswordResetCode::where('user_id', $user->id)->whereNull('used_at')->delete();
                PasswordResetCode::create([
                    'user_id' => $user->id,
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
                ]);

                $user->notify(new PasswordResetNotification($token, $tenant->client_code, self::EXPIRES_MINUTES));
            });
        }

        return response()->json([
            'status' => true,
            'message' => 'If that account exists, we\'ve emailed instructions to reset the password.',
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'client_code' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        [$tenant, $user] = $this->resolve($validated['client_code'], $validated['email']);
        $invalid = ValidationException::withMessages(['token' => 'This reset link is invalid or has expired.']);

        if (! $user) {
            throw $invalid;
        }

        app(TenantContext::class)->runAs($tenant->id, function () use ($user, $validated, $invalid) {
            DB::transaction(function () use ($user, $validated, $invalid) {
                $code = PasswordResetCode::where('user_id', $user->id)
                    ->whereNull('used_at')
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if (! $code || ! hash_equals($code->token_hash, hash('sha256', $validated['token']))) {
                    throw $invalid;
                }

                $code->update(['used_at' => now()]);
                $user->forceFill(['password' => $validated['password'], 'must_change_password' => false])->save();

                // Whoever had the old password is signed out everywhere.
                $user->tokens()->delete();
            });
        });

        return response()->json(['status' => true, 'message' => 'Password reset. You can now log in.']);
    }

    /**
     * @return array{0: ?Tenant, 1: ?User}
     */
    private function resolve(string $clientCode, string $email): array
    {
        $tenant = Tenant::where('client_code', strtolower($clientCode))->first();

        if (! $tenant) {
            return [null, null];
        }

        return [$tenant, app(TenantContext::class)->runAs($tenant->id, fn () => User::where('email', $email)->first())];
    }
}
