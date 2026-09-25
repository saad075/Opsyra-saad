<?php

namespace App\Services;

use App\Models\Company;
use RuntimeException;

/**
 * Asks the company's country deployment to create it, and records the
 * outcome in the register. Safe to retry from the admin dashboard for a
 * company stuck in provisioning_failed.
 */
class CompanyProvisioner
{
    public function provision(Company $company): bool
    {
        try {
            $response = CountryDeployment::for($company->country)->post('tenants', [
                'name' => $company->name,
                'client_code' => $company->client_code,
                'country' => $company->country,
                'admin_name' => $company->admin_name,
                'admin_email' => $company->admin_email,
                'trial_ends_at' => $company->trial_ends_at?->toIso8601String(),
                'plan_code' => $company->plan?->code,
                'features' => $company->plan?->featureCodes() ?? [],
            ]);
        } catch (RuntimeException $e) {
            report($e);
            $company->update(['status' => 'provisioning_failed', 'provision_error' => $e->getMessage()]);

            return false;
        }

        if ($response->successful()) {
            $company->update(['status' => 'trial', 'provision_error' => null]);

            return true;
        }

        $company->update([
            'status' => 'provisioning_failed',
            'provision_error' => "HTTP {$response->status()}: ".($response->json('message') ?? substr($response->body(), 0, 500)),
        ]);

        return false;
    }
}
