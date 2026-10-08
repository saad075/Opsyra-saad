<x-layouts.admin title="Companies">
    <h1 class="text-3xl font-extrabold">Companies</h1>

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
        <label class="text-sm font-semibold">Search
            <input name="q" value="{{ request('q') }}" placeholder="Name, code or email" class="mt-1 block w-64 rounded-lg border border-line bg-white px-3 py-2 font-normal">
        </label>
        <label class="text-sm font-semibold">Status
            <select name="status" class="mt-1 block rounded-lg border border-line bg-white px-3 py-2 font-normal">
                <option value="">All</option>
                @foreach (['trial', 'active', 'past_due', 'suspended', 'cancelled', 'provisioning_failed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-semibold">Country
            <select name="country" class="mt-1 block rounded-lg border border-line bg-white px-3 py-2 font-normal">
                <option value="">All</option>
                @foreach ($countries as $code => $info)
                    <option value="{{ $code }}" @selected(request('country') === $code)>{{ $info['name'] }}</option>
                @endforeach
            </select>
        </label>
        <button class="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white hover:bg-brand-700">Filter</button>
        @if (request()->hasAny(['q', 'status', 'country']))
            <a href="{{ route('admin.companies') }}" class="px-2 py-2 text-sm font-semibold text-muted hover:text-ink">Clear</a>
        @endif
    </form>

    <div class="mt-6 overflow-x-auto rounded-2xl border border-line bg-white">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-canvas text-muted">
                <tr>
                    <th class="px-4 py-3 font-semibold">Company</th>
                    <th class="px-4 py-3 font-semibold">Country</th>
                    <th class="px-4 py-3 font-semibold">Plan</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 text-right font-semibold">Staff</th>
                    <th class="px-4 py-3 text-right font-semibold">Monthly</th>
                    <th class="px-4 py-3 font-semibold">Trial ends</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($companies as $company)
                    <tr class="border-t border-line">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.companies.show', $company) }}" class="font-semibold hover:text-brand-600">{{ $company->name }}</a>
                            <span class="block text-muted">{{ $company->client_code }}@if ($company->is_demo) · demo @endif</span>
                        </td>
                        <td class="px-4 py-3">{{ $company->countryName() }}</td>
                        <td class="px-4 py-3">{{ $company->plan?->name ?? '—' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$company->status" />@if ($company->sync_error) <span class="ml-1 text-amber-700" title="{{ $company->sync_error }}">⚠</span>@endif</td>
                        <td class="px-4 py-3 text-right">{{ number_format($company->billableEmployees()) }}</td>
                        <td class="px-4 py-3 text-right">{{ ($price = $company->monthlyPrice()) !== null ? \App\Support\Pricing::format($price, $company->currency()) : '—' }}</td>
                        <td class="px-4 py-3">{{ $company->status === 'trial' ? $company->trial_ends_at?->format('j M Y') : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-muted">No companies match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $companies->links() }}</div>
</x-layouts.admin>
