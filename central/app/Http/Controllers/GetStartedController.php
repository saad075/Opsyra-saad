<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Plan;
use App\Services\CompanyProvisioner;
use App\Services\CountryDeployment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * "Get started": free 14-day trial, no card (user decision 2026-09-25).
 * The company is registered here, then created on its country deployment,
 * which emails the administrator a set-password link.
 */
class GetStartedController extends Controller
{
    public const TRIAL_DAYS = 14;

    /** Codes that would be confusing or impersonate us. */
    private const RESERVED_CODES = ['admin', 'api', 'app', 'demo', 'help', 'login', 'opsyra', 'root', 'support', 'system', 'test', 'www', 'brainstormers'];

    public function show(Request $request)
    {
        $countries = config('opsyra.countries');

        return view('get-started', [
            'countries' => $countries,
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'selectedCountry' => array_key_exists($request->query('country'), $countries) ? $request->query('country') : null,
            'selectedPlan' => $request->query('plan'),
        ]);
    }

    /** Live check while typing the company code. */
    public function checkCode(Request $request)
    {
        $code = Str::lower(trim((string) $request->query('code')));

        return response()->json(['available' => $this->codeProblem($code) === null, 'message' => $this->codeProblem($code)]);
    }

    public function store(Request $request, CompanyProvisioner $provisioner)
    {
        $request->merge(['client_code' => Str::lower(trim((string) $request->input('client_code')))]);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'client_code' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9][a-z0-9-]*$/', Rule::notIn(self::RESERVED_CODES), Rule::unique('companies', 'client_code')],
            'country' => ['required', Rule::in(array_keys(config('opsyra.countries')))],
            'employees' => ['required', 'integer', 'min:1', 'max:100000'],
            'plan' => ['nullable', Rule::exists('plans', 'code')->where('is_active', true)],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'terms' => ['accepted'],
        ], [
            'client_code.regex' => 'Use lowercase letters, numbers and hyphens, starting with a letter or number.',
            'client_code.not_in' => 'That code is reserved. Please choose another.',
            'client_code.unique' => 'That company code is taken. Please choose another.',
            'terms.accepted' => 'Please accept the terms to continue.',
        ]);

        if (! CountryDeployment::isConnected($validated['country'])) {
            return back()->withInput()->withErrors(['country' => 'Opsyra isn’t open for sign-ups in '.config("opsyra.countries.{$validated['country']}.name").' yet. We’ll be there soon.']);
        }

        $company = Company::create([
            'client_code' => $validated['client_code'],
            'name' => $validated['company_name'],
            'country' => $validated['country'],
            'status' => 'provisioning',
            // A plan chosen on the pricing page; otherwise the middle tier,
            // so the trial shows most of the product.
            'plan_id' => Plan::where('code', $validated['plan'] ?? 'plus')->value('id'),
            'trial_ends_at' => now()->addDays(self::TRIAL_DAYS)->endOfDay(),
            'admin_name' => $validated['admin_name'],
            'admin_email' => strtolower($validated['admin_email']),
            'phone' => $validated['phone'] ?? null,
            'expected_employees' => $validated['employees'],
        ]);

        $provisioner->provision($company);

        // The confirmation page reads the sign-up from this visitor's own
        // session, not a URL parameter anyone could change to peek at
        // other companies.
        return redirect()->route('get-started.done')->with('signed_up_company_id', $company->id);
    }

    public function done(Request $request)
    {
        $company = Company::find($request->session()->get('signed_up_company_id'));

        if (! $company) {
            return redirect()->route('get-started');
        }

        // Survive a refresh of the confirmation page.
        $request->session()->keep('signed_up_company_id');

        return view('get-started-done', ['company' => $company]);
    }

    private function codeProblem(string $code): ?string
    {
        return match (true) {
            strlen($code) < 3 => 'At least 3 characters.',
            ! preg_match('/^[a-z0-9][a-z0-9-]*$/', $code) => 'Lowercase letters, numbers and hyphens only.',
            in_array($code, self::RESERVED_CODES, true) => 'That code is reserved.',
            Company::where('client_code', $code)->exists() => 'That code is taken.',
            default => null,
        };
    }
}
