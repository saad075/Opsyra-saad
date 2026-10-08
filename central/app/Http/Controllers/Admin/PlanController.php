<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Plan names, descriptions, order, and which features each plan unlocks.
 * Prices live on the pricing grid.
 */
class PlanController extends Controller
{
    public function index()
    {
        return view('admin.plans', [
            'plans' => Plan::with('features')->orderBy('sort_order')->get(),
            'features' => config('opsyra.features'),
        ]);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'features' => ['array'],
            'features.*' => ['string', Rule::in(array_keys(config('opsyra.features')))],
        ]);

        DB::transaction(function () use ($plan, $validated) {
            $plan->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $plan->features()->delete();

            foreach (array_unique($validated['features'] ?? []) as $code) {
                $plan->features()->create(['feature_code' => $code]);
            }
        });

        return redirect()->route('admin.plans')->with('status', "{$plan->name} saved.");
    }
}
