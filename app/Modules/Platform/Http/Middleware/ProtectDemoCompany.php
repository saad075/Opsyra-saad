<?php

namespace App\Modules\Platform\Http\Middleware;

use App\Modules\Platform\Models\Tenant;
use App\Modules\Setup\Models\TenantSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public demo company is shared by every visitor and rebuilt nightly.
 * Visitors may try almost everything — add staff, run payroll, approve —
 * but nothing that would lock the next visitor out: the shared demo
 * account's password, its roles and permissions, its own staff record, and
 * role definitions stay as they are.
 */
class ProtectDemoCompany
{
    /** Account and access settings — fixed in the demo. */
    private const BLOCKED_ROUTES = ['auth.change-password'];

    private const BLOCKED_PATHS = ['api/roles/*'];

    /** Routes that act on a staff member named by id / user_id. */
    private const STAFF_PATHS = ['api/staff/*', 'api/payroll/bank-accounts/save'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->isMethodSafe() || ! Tenant::whereKey($user->tenant_id)->value('is_demo')) {
            return $next($request);
        }

        $blocked = $request->routeIs(...self::BLOCKED_ROUTES)
            || $request->is(...self::BLOCKED_PATHS)
            || ($request->is(...self::STAFF_PATHS) && $this->targetsDemoAdmin($request));

        if ($blocked) {
            return response()->json([
                'status' => false,
                'message' => 'This is locked in the demo so every visitor can sign in. Start a free trial to try it for real.',
                'demo_locked' => true,
            ], 403);
        }

        return $next($request);
    }

    private function targetsDemoAdmin(Request $request): bool
    {
        $adminId = (int) TenantSetting::get('demo_admin_user_id');

        return $adminId > 0 && in_array($adminId, array_map('intval', array_filter([$request->input('id'), $request->input('user_id')])), true);
    }
}
