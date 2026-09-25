<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Models\PasswordResetCode;
use App\Modules\Auth\Notifications\WelcomeAdminNotification;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Support\Str;

/**
 * Invites a new company's first administrator by email with a single-use
 * "set your password" link, so no password is ever emailed or passed
 * through Opsyra Central. Reuses the password-reset machinery, with a
 * longer expiry.
 */
class AdminInvitation
{
    public const EXPIRES_HOURS = 72;

    public function send(Tenant $tenant, User $admin): void
    {
        app(TenantContext::class)->runAs($tenant->id, function () use ($tenant, $admin) {
            $token = Str::random(64);

            PasswordResetCode::where('user_id', $admin->id)->whereNull('used_at')->delete();
            PasswordResetCode::create([
                'user_id' => $admin->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(self::EXPIRES_HOURS),
            ]);

            $admin->notify(new WelcomeAdminNotification($token, $tenant, self::EXPIRES_HOURS));
        });
    }
}
