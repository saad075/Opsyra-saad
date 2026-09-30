<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Services\CompanyProvisioner;
use App\Services\CountryDeployment;
use App\Services\SubscriptionSync;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Companies list and per-company management. Every change is saved here
 * first (Central is the source of truth), then pushed to the company's
 * country deployment; a failed push is flagged for retry, never lost.
 */
class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $companies = Company::with('plan.prices')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('country'), fn ($q) => $q->where('country', $request->country))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'ilike', '%'.$request->q.'%')
                ->orWhere('client_code', 'ilike', '%'.$request->q.'%')
                ->orWhere('admin_email', 'ilike', '%'.$request->q.'%')))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.companies.index', ['companies' => $companies, 'countries' => config('opsyra.countries')]);
    }

    public function show(Company $company)
    {
        return view('admin.companies.show', [
            'company' => $company->load('plan.prices'),
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function extendTrial(Request $request, Company $company, SubscriptionSync $sync)
    {
        $days = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:90']])['days'];

        // Extend from the later of today and the current end date.
        $from = $company->trial_ends_at && $company->trial_ends_at->isFuture() ? $company->trial_ends_at : now();

        $company->update(['status' => 'trial', 'trial_ends_at' => $from->copy()->addDays($days)->endOfDay()]);

        return $this->pushed($company, $sync, "Trial extended to {$company->trial_ends_at->format('j M Y')}.");
    }

    public function changePlan(Request $request, Company $company, SubscriptionSync $sync)
    {
        $planId = $request->validate(['plan_id' => ['required', Rule::exists('plans', 'id')]])['plan_id'];

        $company->update(['plan_id' => $planId]);

        return $this->pushed($company->load('plan'), $sync, "Plan changed to {$company->plan->name}.");
    }

    public function setStatus(Request $request, Company $company, SubscriptionSync $sync)
    {
        $status = $request->validate(['status' => ['required', Rule::in(['trial', 'active', 'past_due', 'suspended', 'cancelled'])]])['status'];

        $company->update([
            'status' => $status,
            'suspended_at' => $status === 'suspended' ? now() : null,
        ]);

        return $this->pushed($company, $sync, 'Status set to '.str_replace('_', ' ', $status).'.');
    }

    public function retry(Company $company, CompanyProvisioner $provisioner, SubscriptionSync $sync)
    {
        if ($company->status === 'provisioning_failed') {
            $ok = $provisioner->provision($company);

            return back()->with($ok ? 'status' : 'warning', $ok ? "Workspace ready. A welcome email is on its way to {$company->admin_email}." : 'Still failing: '.$company->provision_error);
        }

        return $this->pushed($company, $sync, 'Country copy updated.');
    }

    /** A fresh "set your password" link for the administrator; the old one stops working. */
    public function resendWelcome(Company $company)
    {
        abort_if(in_array($company->status, ['provisioning', 'provisioning_failed'], true) || $company->is_demo, 404);

        try {
            $response = CountryDeployment::for($company->country)->post("tenants/{$company->client_code}/welcome", ['admin_email' => $company->admin_email]);
        } catch (RuntimeException $e) {
            report($e);

            return back()->with('warning', "Couldn’t ask the {$company->countryName()} deployment: {$e->getMessage()}");
        }

        if (! $response->successful()) {
            return back()->with('warning', "The {$company->countryName()} deployment refused: HTTP {$response->status()}: ".($response->json('message') ?? 'no message'));
        }

        return back()->with('status', "A new welcome email is on its way to {$company->admin_email}.");
    }

    private function pushed(Company $company, SubscriptionSync $sync, string $message)
    {
        if ($sync->push($company)) {
            return back()->with('status', $message);
        }

        return back()->with('warning', "{$message} Saved here, but the {$company->countryName()} deployment wasn’t updated: {$company->sync_error}. Use “Retry sync”.");
    }
}
