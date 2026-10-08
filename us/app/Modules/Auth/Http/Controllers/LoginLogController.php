<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Adapted from SLMS360's login-log view (permission HRMS-ViewLoginLogs).
 */
class LoginLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-login-logs');

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', ValidationRule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $data = LoginLog::with('user:id,name,email')
            ->when($validated['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->orderByDesc('login_at')
            ->limit(200)
            ->get();

        return response()->json(['status' => true, 'message' => 'Login history', 'data' => $data]);
    }
}
