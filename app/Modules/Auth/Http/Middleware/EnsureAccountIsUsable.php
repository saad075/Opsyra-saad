<?php

namespace App\Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every API request after the tenant is resolved:
 *
 * - An account awaiting onboarding approval, or offboarded, can't use the
 *   API even with a token it still holds. ('pending' — approved but not yet
 *   started — is allowed: new starters have onboarding tasks to do.)
 * - Someone holding a password they didn't choose must change it before
 *   doing anything else; only the routes needed to do that are open.
 */
class EnsureAccountIsUsable
{
    private const BLOCKED_STATUSES = ['pending_approval', 'offboarded'];

    private const OPEN_WHILE_CHANGING_PASSWORD = ['auth.me', 'auth.logout', 'auth.change-password'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (in_array($user->status, self::BLOCKED_STATUSES, true)) {
            return response()->json(['status' => false, 'message' => 'Your account is not active.'], 403);
        }

        if ($user->must_change_password && ! $request->routeIs(...self::OPEN_WHILE_CHANGING_PASSWORD)) {
            return response()->json([
                'status' => false,
                'message' => 'Please change your password to continue.',
                'password_change_required' => true,
            ], 403);
        }

        return $next($request);
    }
}
