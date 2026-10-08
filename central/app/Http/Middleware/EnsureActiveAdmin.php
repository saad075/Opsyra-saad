<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deactivated BrainStormers account is signed out on its next request,
 * not just refused at the next login.
 */
class EnsureActiveAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
