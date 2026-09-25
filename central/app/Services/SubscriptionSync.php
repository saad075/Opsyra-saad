<?php

namespace App\Services;

use App\Models\Company;
use RuntimeException;

/**
 * Pushes a company's subscription (status, trial end, plan, features) from
 * Central — the source of truth — to its country deployment's enforcement
 * copy, and pulls back aggregate usage (active staff counts).
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

        $updated = 0;

        foreach ($response->json('data', []) as $row) {
            $updated += Company::where('country', $country)
                ->where('client_code', $row['client_code'] ?? null)
                ->update(['active_employees' => (int) ($row['active_employees'] ?? 0), 'usage_reported_at' => now()]);
        }

        return $updated;
    }
}
