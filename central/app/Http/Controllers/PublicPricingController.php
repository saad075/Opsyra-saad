<?php

namespace App\Http\Controllers;

use App\Models\CountryPricing;
use App\Models\Plan;
use Illuminate\Http\Request;

/**
 * Public pricing: a country picker, an employee count, and each plan's
 * monthly price — or "plans coming soon" for any country whose prices
 * haven't been published.
 */
class PublicPricingController extends Controller
{
    public function __invoke(Request $request)
    {
        $countries = config('opsyra.countries');
        $country = array_key_exists($request->query('country'), $countries) ? $request->query('country') : 'us';
        $employees = max(1, min(100000, (int) $request->query('employees', 25)));

        $published = CountryPricing::isPublished($country);

        return view('pricing', [
            'countries' => $countries,
            'country' => $country,
            'employees' => $employees,
            'published' => $published,
            'features' => config('opsyra.features'),
            'plans' => $published
                ? Plan::with(['features', 'prices' => fn ($q) => $q->where('country', $country)])
                    ->where('is_active', true)
                    ->whereHas('prices', fn ($q) => $q->where('country', $country))
                    ->orderBy('sort_order')
                    ->get()
                : collect(),
        ]);
    }
}
