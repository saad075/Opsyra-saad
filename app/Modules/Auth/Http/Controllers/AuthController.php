<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Models\LoginLog;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Setup\Models\TenantSetting;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Adapted from SLMS360's Client/ClientAuthController::login(). Changes:
 * the company is identified by client code (emails are only unique within a
 * company here); the password is checked before revealing anything about the
 * account (SLMS360 answered "account not active" for any known email, which
 * let anyone probe which emails exist); attempts are rate-limited; and a
 * suspended/cancelled company is refused (full subscription enforcement
 * arrives with Opsyra Central — docs/research/subscription-architecture.md).
 */
class AuthController extends Controller
{
    private const FAILED = 'These details don\'t match our records.';

    public function login(Request $request)
    {
        $validated = $request->validate([
            'client_code' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $throttleKey = strtolower($validated['client_code'].'|'.$validated['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ])->status(429);
        }

        $tenant = Tenant::where('client_code', strtolower($validated['client_code']))->first();

        $user = $tenant
            ? app(TenantContext::class)->runAs($tenant->id, fn () => User::where('email', $validated['email'])->first())
            : null;

        // Always hash-check, even with no user, so response time doesn't
        // reveal whether the email exists.
        $passwordOk = Hash::check($validated['password'], $user?->password ?? Hash::make('not-a-real-password'));

        if (! $user || ! $passwordOk) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages(['email' => self::FAILED]);
        }

        RateLimiter::clear($throttleKey);

        if (! in_array($tenant->status, ['trial', 'active'], true)) {
            return response()->json(['status' => false, 'message' => 'Your company\'s account is suspended. Please contact your administrator.'], 403);
        }

        if (in_array($user->status, ['pending_approval', 'offboarded'], true)) {
            return response()->json(['status' => false, 'message' => 'Your account is not active. Please contact your administrator.'], 403);
        }

        $token = app(TenantContext::class)->runAs($tenant->id, function () use ($user, $validated, $request) {
            LoginLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'device_name' => $validated['device_name'] ?? null,
                'login_at' => now(),
            ]);

            return $user->createToken($validated['device_name'] ?? 'api')->plainTextToken;
        });

        return response()->json([
            'status' => true,
            'message' => 'Logged in.',
            'data' => [
                'token' => $token,
                'must_change_password' => (bool) $user->must_change_password,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $tenant = Tenant::findOrFail($user->tenant_id);

        return response()->json([
            'status' => true,
            'message' => 'Current user',
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'designation', 'avatar', 'status']),
                'must_change_password' => (bool) $user->must_change_password,
                'company' => [
                    'name' => TenantSetting::get('company_name', $tenant->name),
                    'client_code' => $tenant->client_code,
                    'country' => $tenant->country,
                ],
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['status' => true, 'message' => 'Logged out.']);
    }

    /**
     * Also signs out every other device — the usual reason to change a
     * password is suspecting someone else has it.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(10)->letters()->numbers()],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }

        $user->forceFill(['password' => $validated['password'], 'must_change_password' => false])->save();

        $currentId = $user->currentAccessToken()?->id;
        $user->tokens()->when($currentId, fn ($q) => $q->whereKeyNot($currentId))->delete();

        return response()->json(['status' => true, 'message' => 'Password changed.']);
    }
}
