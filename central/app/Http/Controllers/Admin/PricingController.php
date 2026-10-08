<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CountryPricing;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The pricing grid: for one country, each active plan's three numbers
 * (monthly price, employees included, price per extra employee), plus the
 * switch that makes the country's prices public.
 */
class PricingController extends Controller
{
    public function edit(string $country = 'us')
    {
        abort_unless(array_key_exists($country, config('opsyra.countries')), 404);

        return view('admin.pricing', [
            'country' => $country,
            'countries' => config('opsyra.countries'),
            'plans' => Plan::with(['prices', 'features'])->where('is_active', true)->orderBy('sort_order')->get(),
            'published' => CountryPricing::isPublished($country),
        ]);
    }

    public function update(Request $request, string $country)
    {
        abort_unless(array_key_exists($country, config('opsyra.countries')), 404);

        $planIds = Plan::where('is_active', true)->pluck('id')->all();

        $validated = $request->validate([
            'prices' => ['required', 'array'],
            'prices.*' => ['array'],
            'prices.*.base_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'prices.*.included_employees' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'prices.*.extra_employee_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $currency = config("opsyra.countries.{$country}.currency");

        DB::transaction(function () use ($validated, $planIds, $country, $currency, $request) {
            foreach ($validated['prices'] as $planId => $row) {
                if (! in_array((int) $planId, $planIds, true)) {
                    continue;
                }

                $filled = collect($row)->filter(fn ($v) => $v !== null && $v !== '');

                // A plan left completely blank simply isn't offered here yet;
                // half-filled is a mistake.
                if ($filled->isEmpty()) {
                    Plan::find($planId)->prices()->where('country', $country)->delete();

                    continue;
                }

                if ($filled->count() < 3) {
                    throw ValidationException::withMessages(["prices.{$planId}" => 'Fill in all three numbers for this plan, or leave all three empty.']);
                }

                Plan::find($planId)->prices()->updateOrCreate(['country' => $country], [
                    'currency' => $currency,
                    'base_price' => $row['base_price'],
                    'included_employees' => $row['included_employees'],
                    'extra_employee_price' => $row['extra_employee_price'],
                    'updated_by' => $request->user()->id,
                ]);
            }
        });

        return redirect()->route('admin.pricing', $country)->with('status', 'Prices saved.');
    }

    /**
     * Publishing needs a price for every active plan, so the public page
     * never shows a plan without one.
     */
    public function publish(Request $request, string $country)
    {
        abort_unless(array_key_exists($country, config('opsyra.countries')), 404);

        $publish = $request->validate(['publish' => ['required', Rule::in(['0', '1'])]])['publish'] === '1';

        if ($publish) {
            $missing = Plan::where('is_active', true)
                ->whereDoesntHave('prices', fn ($q) => $q->where('country', $country))
                ->pluck('name');

            if ($missing->isNotEmpty()) {
                return back()->withErrors(['publish' => 'Set prices for '.$missing->join(', ', ' and ').' before publishing.']);
            }
        }

        CountryPricing::updateOrCreate(['country' => $country], [
            'is_published' => $publish,
            'published_at' => $publish ? now() : null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.pricing', $country)
            ->with('status', $publish ? 'Published. These prices are now on the website.' : 'Unpublished. The website shows “plans coming soon”.');
    }
}
