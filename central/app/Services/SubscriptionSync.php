<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Pushes a company's subscription (status, trial end, plan, features) from
 * Central — the source of truth — to its country deployment's enforcement
 * copy, and pulls back aggregate usage (active staff counts) and whether
 * the country's email is getting out.
 */
class SubscriptionSync
{
    /**
     * Central's statuses are richer than the country's tenants.status
     * (trial, active, suspended, cancelled). Past-due companies keep
     * working until someone decides to suspend them.
     */
    private const COUNTRY_STATUS = [
        'trial' => 'trial',
        'active' => 'active',
        'past_due' => 'active',
        'suspended' => 'suspended',
        'cancelled' => 'cancelled',
    ];

    public function push(Company $company): bool
    {
        $countryStatus = self::COUNTRY_STATUS[$company->status] ?? null;

        if ($countryStatus === null) {
            return false; // Not provisioned yet — nothing to push to.
        }

        try {
            $response = CountryDeployment::for($company->country)->post("tenants/{$company->client_code}/subscription", [
                'status' => $countryStatus,
                'trial_ends_at' => $company->trial_ends_at?->toIso8601String(),
                'plan_code' => $company->plan?->code,
                'features' => $company->plan?->featureCodes() ?? [],
            ]);
        } catch (RuntimeException $e) {
            report($e);
            $company->update(['sync_error' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            $company->update(['sync_error' => "HTTP {$response->status()}: ".($response->json('message') ?? 'no message')]);

            return false;
        }

        $company->update(['synced_at' => now(), 'sync_error' => null]);

        return true;
    }

    /**
     * @return int companies updated
     */
    public function pullUsage(string $country): int
    {
        $response = CountryDeployment::for($country)->post('usage', []);

        if (! $response->successful()) {
            throw new RuntimeException("Usage request to {$country} failed: HTTP {$response->status()}");
        }

        if (is_array($mail = $response->json('mail'))) {
            Cache::forever("country_mail.{$country}", $mail + ['checked_at' => now()->toIso8601String()]);
        }

        $updated = 0;

        foreach ($response->json('data', []) as $row) {
            $changes = ['active_employees' => (int) ($row['active_employees'] ?? 0), 'usage_reported_at' => now()];

            // The account owner is the customer contact; it changes when
            // they hand ownership on inside the app.
            if (! empty($row['owner']['email'])) {
                $changes += ['admin_name' => $row['owner']['name'], 'admin_email' => strtolower($row['owner']['email'])];
            }

            $updated += Company::where('country', $country)
                ->where('client_code', $row['client_code'] ?? null)
                ->update($changes);
        }

        return $updated;
    }

    /**
     * What the country last said about its email (see its MailHealth), as
     * of the last usage pull — hourly.
     *
     * @return array{failing: bool, last_sent_at: ?string, last_failed_at: ?string, last_error: ?string, checked_at: string}|null
     */
    public static function mailHealth(string $country): ?array
    {
        return Cache::get("country_mail.{$country}");
    }
}
