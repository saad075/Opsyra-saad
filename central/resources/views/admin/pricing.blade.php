<x-layouts.admin title="Pricing">
    @php($currency = $countries[$country]['currency'])

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold">Pricing</h1>
            <p class="mt-1 text-muted">Monthly price = <strong>starting price</strong> + each employee over the <strong>included</strong> number × <strong>price per extra employee</strong>.</p>
        </div>
        <a href="{{ route('pricing', ['country' => $country]) }}" target="_blank" class="text-sm font-semibold text-brand-600 hover:text-brand-700">View public page ↗</a>
    </div>

    {{-- Country tabs --}}
    <div class="mt-8 flex flex-wrap gap-2 border-b border-line">
        @foreach ($countries as $code => $info)
            <a href="{{ route('admin.pricing', $code) }}" @class([
                '-mb-px rounded-t-lg border px-4 py-2 font-semibold',
                'border-line border-b-white bg-white' => $code === $country,
                'border-transparent text-muted hover:text-ink' => $code !== $country,
            ])>{{ $info['name'] }} <span class="text-xs font-normal text-muted">{{ $info['currency'] }}</span></a>
        @endforeach
    </div>

    <div class="rounded-b-2xl rounded-tr-2xl border border-t-0 border-line bg-white p-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl bg-canvas px-4 py-3">
            <p>
                @if ($published)
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-brand-600"></span>
                    <strong>Published.</strong> These prices are on the website for {{ $countries[$country]['name'] }}.
                @else
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    <strong>Draft.</strong> The website shows “plans coming soon” for {{ $countries[$country]['name'] }}.
                @endif
            </p>
            <form method="POST" action="{{ route('admin.pricing.publish', $country) }}">
                @csrf
                <input type="hidden" name="publish" value="{{ $published ? '0' : '1' }}">
                <button class="rounded-lg px-4 py-2 font-semibold {{ $published ? 'border border-line hover:bg-white' : 'bg-brand-600 text-white hover:bg-brand-700' }}">
                    {{ $published ? 'Unpublish' : 'Publish these prices' }}
                </button>
            </form>
        </div>

        <form method="POST" action="{{ route('admin.pricing.update', $country) }}">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left">
                    <thead>
                        <tr class="text-sm text-muted">
                            <th class="pb-3 font-semibold">Plan</th>
                            <th class="pb-3 font-semibold">Starting price / month ({{ $currency }})</th>
                            <th class="pb-3 font-semibold">Employees included</th>
                            <th class="pb-3 font-semibold">Each extra employee ({{ $currency }})</th>
                            <th class="pb-3 font-semibold">Example: 25 employees</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($plans as $plan)
                            @php($price = $plan->priceFor($country))
                            <tr class="border-t border-line align-top">
                                <td class="py-4 pr-4">
                                    <p class="font-display font-bold">{{ $plan->name }}</p>
                                    <p class="text-sm text-muted">{{ count($plan->featureCodes()) }} features</p>
                                </td>
                                @foreach (['base_price' => '0.01', 'included_employees' => '1', 'extra_employee_price' => '0.01'] as $field => $step)
                                    <td class="py-4 pr-4">
                                        <input type="number" min="0" step="{{ $currency === 'OMR' && $step !== '1' ? '0.001' : $step }}"
                                            name="prices[{{ $plan->id }}][{{ $field }}]"
                                            value="{{ old("prices.{$plan->id}.{$field}", $price?->{$field}) }}"
                                            class="w-36 rounded-lg border border-line px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    </td>
                                @endforeach
                                <td class="py-4 font-semibold">
                                    {{ $price ? \App\Support\Pricing::format($price->monthlyFor(25), $currency) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-sm text-muted">Leave all three empty to not offer a plan in this country yet.</p>
            <div class="mt-6 flex justify-end">
                <button class="rounded-lg bg-brand-600 px-6 py-2.5 font-bold text-white hover:bg-brand-700">Save prices</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
