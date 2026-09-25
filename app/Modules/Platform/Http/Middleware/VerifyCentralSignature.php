<?php

namespace App\Modules\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the internal endpoints that only Opsyra Central may call
 * (provision a company, update its subscription, read usage). Same idea as
 * SLMS360's subscription bridge (HMAC-SHA256 over the request with a shared
 * secret), tightened: the signature also covers a timestamp, and requests
 * more than 5 minutes old are refused, so a captured request can't be
 * replayed later.
 *
 *   X-Opsyra-Timestamp: <unix seconds>
 *   X-Opsyra-Signature: hex(hmac_sha256("<timestamp>.<raw body>", OPSYRA_CENTRAL_SECRET))
 */
class VerifyCentralSignature
{
    private const MAX_SKEW_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.central.secret');

        if (! $secret) {
            return response()->json(['status' => false, 'message' => 'Central link is not configured on this deployment.'], 503);
        }

        $timestamp = (string) $request->header('X-Opsyra-Timestamp', '');
        $signature = (string) $request->header('X-Opsyra-Signature', '');

        if (! ctype_digit($timestamp) || abs(time() - (int) $timestamp) > self::MAX_SKEW_SECONDS) {
            return response()->json(['status' => false, 'message' => 'Stale or missing timestamp.'], 401);
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            return response()->json(['status' => false, 'message' => 'Invalid signature.'], 401);
        }

        return $next($request);
    }
}
