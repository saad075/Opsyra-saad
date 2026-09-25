<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;

/**
 * The subscription dashboard: how many companies, in what state, where,
 * what they're worth per month, and what needs attention.
 */
class DashboardController extends Controller
{
    public function __invoke()
    {
        // The public demo company isn't a customer.
        $companies = Company::with('plan.prices')->where('is_demo', false)->get();

        $byStatus = $companies->countBy('status');

        // Monthly revenue per currency — never summed across currencies.
        $revenue = $companies->whereIn('status', Company::PAYING)
            ->groupBy(fn (Company $c) => $c->currency())
            ->map(fn ($group) => $group->sum(fn (Company $c) => $c->monthlyPrice() ?? 0));

        $trialPipeline = $companies->where('status', 'trial')
            ->groupBy(fn (Company $c) => $c->currency())
            ->map(fn ($group) => $group->sum(fn (Company $c) => $c->monthlyPrice() ?? 0));

        // Sign-ups per week, last 8 weeks, for the bar chart.
        $weeks = collect(range(7, 0))->map(function (int $weeksAgo) use ($companies) {
            $start = now()->startOfWeek()->subWeeks($weeksAgo);

            return [
                'label' => $start->format('j M'),
                'count' => $companies->filter(fn (Company $c) => $c->created_at->between($start, $start->copy()->endOfWeek()))->count(),
            ];
        });

        return view('admin.dashboard', [
            'total' => $companies->count(),
            'byStatus' => $byStatus,
            'revenue' => $revenue,
            'trialPipeline' => $trialPipeline,
            'weeks' => $weeks,
            'maxWeek' => max(1, $weeks->max('count')),
            'byCountry' => collect(config('opsyra.countries'))->map(fn ($info, $code) => [
                'name' => $info['name'],
                'total' => $companies->where('country', $code)->count(),
                'trial' => $companies->where('country', $code)->where('status', 'trial')->count(),
                'paying' => $companies->where('country', $code)->whereIn('status', Company::PAYING)->count(),
                'staff' => $companies->where('country', $code)->sum(fn (Company $c) => $c->billableEmployees()),
            ]),
            'trialsEndingSoon' => $companies->where('status', 'trial')
                ->filter(fn (Company $c) => $c->trial_ends_at && $c->trial_ends_at->between(now(), now()->addDays(7)))
                ->sortBy('trial_ends_at'),
            'trialsExpired' => $companies->where('status', 'trial')->filter(fn (Company $c) => $c->trial_ends_at?->isPast()),
            'needsAttention' => $companies->filter(fn (Company $c) => $c->status === 'provisioning_failed' || $c->sync_error),
            'recent' => $companies->sortByDesc('created_at')->take(8),
        ]);
    }
}
