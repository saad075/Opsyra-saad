<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Jobs\SendSignInLink;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Support\TenantContext;

/**
 * Invites a new company's first administrator by email with a single-use
 * "set your password" link, so no password is ever emailed or passed
 * through Opsyra Central. Reuses the password-reset machinery, with a
 * longer expiry. Queued (SendSignInLink): the company exists whether or
 * not the email goes out first time.
 */
class AdminInvitation
{
    public const EXPIRES_HOURS = 72;

    public function send(Tenant $tenant, User $admin): void
    {
        app(TenantContext::class)->runAs($tenant->id, function () use ($admin) {
            SendSignInLink::dispatch($admin->id, SendSignInLink::WELCOME, self::EXPIRES_HOURS * 60);
        });
    }
}
