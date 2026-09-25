<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * "Log in" on the website: the visitor gives their company code, Central
 * looks it up in the register, and sends them to their own country's app
 * with the code filled in. Passwords are never entered here — they go to
 * the country deployment that holds the account.
 */
class CompanyLoginController extends Controller
{
    public function show(Request $request)
    {
        return view('login', ['code' => $request->query('code', '')]);
    }

    public function submit(Request $request)
    {
        $code = strtolower(trim($request->validate([
            'client_code' => ['required', 'string', 'max:50'],
        ])['client_code']));

        $company = Company::where('client_code', $code)->first();

        if (! $company || ! in_array($company->status, Company::REACHABLE, true) || ! $company->appUrl()) {
            throw ValidationException::withMessages([
                'client_code' => $company && $company->status === 'suspended'
                    ? 'This company’s account is suspended. Please contact your administrator.'
                    : 'We couldn’t find a company with that code. Check with your administrator.',
            ]);
        }

        return redirect()->away(rtrim($company->appUrl(), '/').'/login?'.http_build_query(['client_code' => $company->client_code]));
    }
}
