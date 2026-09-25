<x-layouts.admin title="Dashboard">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold">Subscriptions</h1>
            <p class="mt-1 text-muted">Every Opsyra company across all countries. The public demo company is excluded.</p>
        </div>
        <a href="{{ route('admin.companies') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">All companies →</a>
    </div>

    {{-- KPI cards --}}
    @php
        $kpis = [
            ['Companies', $total, null, route('admin.companies')],
            ['On trial', $byStatus['trial'] ?? 0, 'sky', route('admin.companies', ['status' => 'trial'])],
            ['Paying', ($byStatus['active'] ?? 0) + ($byStatus['past_due'] ?? 0), 'brand', route('admin.companies', ['status' => 'active'])],
            ['Past due', $byStatus['past_due'] ?? 0, 'amber', route('admin.companies', ['status' => 'past_due'])],
            ['Suspended', $byStatus['suspended'] ?? 0, 'red', route('admin.companies', ['status' => 'suspended'])],
        ];
    @endphp
    <div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-5">
        @foreach ($kpis as [$label, $value, $tone, $link])
            <a href="{{ $link }}" class="rounded-2xl border border-line bg-white p-5 transition hover:border-brand-300">
                <p class="text-sm font-semibold text-muted">{{ $label }}</p>
                <p class="mt-2 font-display text-3xl font-extrabold">{{ number_format($value) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Revenue --}}
        <div class="rounded-2xl border border-line bg-white p-6">
            <h2 class="font-bold">Monthly revenue</h2>
            <p class="text-sm text-muted">Paying companies, at their plan’s price for their staff count.</p>
            <div class="mt-4 space-y-2">
                @forelse ($revenue as $currency => $amount)
                    <p class="font-display text-2xl font-extrabold">{{ \App\Support\Pricing::format($amount, $currency) }}</p>
                @empty
                    <p class="font-display text-2xl font-extrabold text-muted">—</p>
                    <p class="text-sm text-muted">No paying companies yet.</p>
                @endforelse
            </div>
            @if ($trialPipeline->filter()->isNotEmpty())
                <p class="mt-5 border-t border-line pt-4 text-sm text-muted">
                    Trials worth, if they convert:
                    @foreach ($trialPipeline->filter() as $currency => $amount)
                        <strong class="text-ink">{{ \App\Support\Pricing::format($amount, $currency) }}</strong>@if (! $loop->last), @endif
                    @endforeach
                    / month
                </p>
            @endif
            <p class="mt-3 text-xs text-muted">Prices use each country’s draft or published plan prices. Not yet charged — billing comes later.</p>
        </div>

        {{-- Sign-ups chart --}}
        <div class="rounded-2xl border border-line bg-white p-6 lg:col-span-2">
            <h2 class="font-bold">New sign-ups</h2>
            <p class="text-sm text-muted">Per week, last 8 weeks.</p>
            <div class="mt-6 flex h-40 items-end gap-3" role="img" aria-label="Sign-ups per week">
                @foreach ($weeks as $week)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <span class="text-xs font-bold">{{ $week['count'] ?: '' }}</span>
                        <div class="w-full rounded-t-md bg-brand-600" style="height: {{ max(4, round($week['count'] / $maxWeek * 120)) }}px; opacity: {{ $week['count'] ? 1 : 0.15 }}"></div>
                        <span class="text-xs text-muted">{{ $week['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if ($needsAttention->isNotEmpty() || $trialsExpired->isNotEmpty())
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
            <h2 class="font-bold text-amber-900">Needs attention</h2>
            <ul class="mt-3 space-y-2">
                @foreach ($needsAttention as $company)
                    <li class="flex flex-wrap items-center justify-between gap-2">
                        <span><a href="{{ route('admin.companies.show', $company) }}" class="font-semibold underline">{{ $company->name }}</a>
                            — {{ $company->status === 'provisioning_failed' ? 'workspace wasn’t created' : 'country copy out of date' }}</span>
                        <form method="POST" action="{{ route('admin.companies.retry', $company) }}">@csrf<button class="rounded-lg bg-white px-3 py-1 text-sm font-semibold shadow-sm hover:bg-amber-100">Retry</button></form>
                    </li>
                @endforeach
                @foreach ($trialsExpired as $company)
                    <li><a href="{{ route('admin.companies.show', $company) }}" class="font-semibold underline">{{ $company->name }}</a> — trial ended {{ $company->trial_ends_at->diffForHumans() }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- By country --}}
        <div class="rounded-2xl border border-line bg-white p-6">
            <h2 class="font-bold">By country</h2>
            <table class="mt-4 w-full text-left text-sm">
                <thead class="text-muted">
                    <tr><th class="pb-2 font-semibold">Country</th><th class="pb-2 text-right font-semibold">Companies</th><th class="pb-2 text-right font-semibold">Trial</th><th class="pb-2 text-right font-semibold">Paying</th><th class="pb-2 text-right font-semibold">Staff</th></tr>
                </thead>
                <tbody>
                    @foreach ($byCountry as $code => $row)
                        <tr class="border-t border-line">
                            <td class="py-2.5 font-semibold"><a href="{{ route('admin.companies', ['country' => $code]) }}" class="hover:text-brand-600">{{ $row['name'] }}</a></td>
                            <td class="py-2.5 text-right">{{ $row['total'] }}</td>
                            <td class="py-2.5 text-right">{{ $row['trial'] }}</td>
                            <td class="py-2.5 text-right">{{ $row['paying'] }}</td>
                            <td class="py-2.5 text-right">{{ number_format($row['staff']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Trials ending --}}
        <div class="rounded-2xl border border-line bg-white p-6">
            <h2 class="font-bold">Trials ending in the next 7 days</h2>
            @forelse ($trialsEndingSoon as $company)
                <div class="mt-3 flex items-center justify-between gap-3 border-t border-line pt-3 first-of-type:border-0">
                    <a href="{{ route('admin.companies.show', $company) }}" class="font-semibold hover:text-brand-600">{{ $company->name }}</a>
                    <span class="text-sm text-muted">{{ $company->trial_ends_at->format('D j M') }} · {{ $company->billableEmployees() }} staff</span>
                </div>
            @empty
                <p class="mt-3 text-sm text-muted">None this week.</p>
            @endforelse
        </div>
    </div>

    {{-- Recent sign-ups --}}
    <div class="mt-6 rounded-2xl border border-line bg-white p-6">
        <h2 class="font-bold">Recent sign-ups</h2>
        <table class="mt-4 w-full text-left text-sm">
            <tbody>
                @forelse ($recent as $company)
                    <tr class="border-t border-line first:border-0">
                        <td class="py-2.5"><a href="{{ route('admin.companies.show', $company) }}" class="font-semibold hover:text-brand-600">{{ $company->name }}</a> <span class="text-muted">{{ $company->client_code }}</span></td>
                        <td class="py-2.5">{{ $company->countryName() }}</td>
                        <td class="py-2.5">{{ $company->plan?->name ?? '—' }}</td>
                        <td class="py-2.5"><x-status-badge :status="$company->status" /></td>
                        <td class="py-2.5 text-right text-muted">{{ $company->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td class="text-muted">No sign-ups yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
