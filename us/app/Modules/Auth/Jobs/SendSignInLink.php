<?php

namespace App\Modules\Auth\Jobs;

use App\Models\User;
use App\Modules\Auth\Models\PasswordResetCode;
use App\Modules\Auth\Notifications\PasswordResetNotification;
use App\Modules\Auth\Notifications\WelcomeAdminNotification;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

/**
 * Emails a single-use "set your password" link: the welcome for a new
 * company's first administrator, or a forgotten-password reset. Sent from
 * the queue so a slow or failing mail server can't fail the request that
 * asked for it — in September 2026 an out-of-credit SMTP account turned
 * three trial sign-ups into HTTP 500s after their companies were created.
 *
 * Carries ids only: a queued notification would restore the User before
 * the tenant context exists, and RLS would hide it. The token is minted
 * here, when the email actually goes, and replaces any unused one.
 */
class SendSignInLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TenantAwareJob;

    public const WELCOME = 'welcome';

    public const RESET = 'reset';

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public int $userId, public string $kind, public int $expiresMinutes)
    {
        $this->captureTenantContext();
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $tenant = Tenant::find($this->tenantId);

        if (! $user || ! $tenant) {
            return; // Removed since it was queued: nothing to send.
        }

        $token = Str::random(64);

        PasswordResetCode::where('user_id', $user->id)->whereNull('used_at')->delete();
        PasswordResetCode::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes($this->expiresMinutes),
        ]);

        $user->notify($this->kind === self::WELCOME
            ? new WelcomeAdminNotification($token, $tenant, intdiv($this->expiresMinutes, 60))
            : new PasswordResetNotification($token, $tenant->client_code, $this->expiresMinutes));
    }
}
