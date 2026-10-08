<?php

namespace App\Modules\Platform\Support;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Whether this deployment's email is getting out: the last send that
 * worked and the last one that didn't. Reported to Opsyra Central with
 * usage, so a mail outage shows on the super-admin dashboard instead of
 * only in a log file (the September 2026 out-of-credit outage went
 * unnoticed for a day).
 */
class MailHealth
{
    private const KEY = 'platform.mail_health';

    public static function recordSent(): void
    {
        self::put(['last_sent_at' => now()->toIso8601String()]);
    }

    public static function recordFailure(Throwable $e): void
    {
        self::put([
            'last_failed_at' => now()->toIso8601String(),
            'last_error' => mb_substr($e->getMessage(), 0, 300),
        ]);
    }

    /**
     * @return array{failing: bool, last_sent_at: ?string, last_failed_at: ?string, last_error: ?string}
     */
    public static function summary(): array
    {
        $state = self::state();
        $failedAt = $state['last_failed_at'] ?? null;
        $sentAt = $state['last_sent_at'] ?? null;

        return [
            // Failing until a send succeeds after the last failure.
            'failing' => $failedAt !== null && ($sentAt === null || $sentAt < $failedAt),
            'last_sent_at' => $sentAt,
            'last_failed_at' => $failedAt,
            'last_error' => $state['last_error'] ?? null,
        ];
    }

    private static function state(): array
    {
        try {
            return Cache::get(self::KEY, []);
        } catch (Throwable) {
            return []; // Health reporting must never break the thing it reports on.
        }
    }

    private static function put(array $changes): void
    {
        try {
            Cache::forever(self::KEY, array_merge(self::state(), $changes));
        } catch (Throwable) {
            // As above.
        }
    }
}
