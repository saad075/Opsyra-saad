<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

/**
 * For the staff mobile app: company code → the country server that holds
 * that company. The app calls this once (at first sign-in) and then talks
 * to the returned server only. Same answer as the website's "Log in" page,
 * as JSON; nothing about the company beyond where it lives.
 */
class ResolveCompanyController extends Controller
{
    public function __invoke(Request $request)
    {
        $code = strtolower(trim($request->validate(['client_code' => ['required', 'string', 'max:50']])['client_code']));

        $company = Company::where('client_code', $code)->first();

        if (! $company || ! in_array($company->status, Company::REACHABLE, true) || ! $company->appUrl()) {
            $suspended = $company && $company->status === 'suspended';

            return response()->json([
                'status' => false,
                'message' => $suspended
                    ? 'This company’s account is suspended. Please contact your administrator.'
                    : 'We couldn’t find a company with that code. Check with your administrator.',
            ], $suspended ? 403 : 404);
        }

        $base = rtrim($company->appUrl(), '/');

        return response()->json([
            'status' => true,
            'data' => [
                'client_code' => $company->client_code,
                'country' => $company->country,
                'api_base_url' => $base.'/api',
            ],
        ]);
    }
}
