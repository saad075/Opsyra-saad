<x-layouts.marketing title="Pricing">
    <section class="bg-gradient-to-b from-canvas to-white">
        <div class="mx-auto max-w-7xl px-4 pb-24 pt-16 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-brand-600">Pricing</p>
                <h1 class="mt-3 text-4xl font-extrabold sm:text-5xl">Simple plans that grow with your team.</h1>
                <p class="mt-5 text-lg text-muted">Every plan starts with a 14-day free trial. No card needed.</p>
            </div>

            <form method="GET" action="{{ route('pricing') }}" class="mx-auto mt-10 flex max-w-xl flex-wrap items-end justify-center gap-4">
                <label class="text-sm font-semibold">
                    Country
                    <select name="country" onchange="this.form.submit()" class="mt-1 block rounded-lg border border-line bg-white px-3 py-2">
                        @foreach ($countries as $code => $info)
                            <option value="{{ $code }}" @selected($code === $country)>{{ $info['name'] }}</option>
                        @endforeach
                    </select>
                </label>
                @if ($published)
                    <label class="text-sm font-semibold">
                        Employees
                        <input type="number" name="employees" min="1" max="100000" value="{{ $employees }}" class="mt-1 block w-28 rounded-lg border border-line bg-white px-3 py-2">
                    </label>
                    <button class="rounded-lg border border-line bg-white px-4 py-2 font-semibold hover:border-brand-600">Update</button>
                @endif
            </form>

            @if (! $published)
                <div class="mx-auto mt-12 max-w-xl rounded-2xl border border-line bg-white p-10 text-center">
                    <h2 class="text-2xl font-extrabold">Plans coming soon</h2>
                    <p class="mt-3 text-muted">We’re finalising pricing for {{ $countries[$country]['name'] }}. In the meantime, try everything free for 14 days.</p>
                    <a href="{{ route('get-started') }}" class="mt-7 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-3 font-bold text-white hover:bg-brand-700">Start your free trial <span aria-hidden="true">↗</span></a>
                </div>
            @else
                {{-- Written out in full: Tailwind can't see class names built from variables. --}}
                <div @class([
                    'mt-12 grid gap-6',
                    'mx-auto max-w-md' => $plans->count() === 1,
                    'lg:grid-cols-2' => $plans->count() === 2,
                    'lg:grid-cols-3' => $plans->count() >= 3,
                ])>
                    @foreach ($plans as $i => $plan)
                        @php($price = $plan->prices->first())
                        <div @class(['flex flex-col rounded-3xl border bg-white p-8', 'border-brand-600 shadow-xl shadow-brand-600/10' => $i === 1, 'border-line' => $i !== 1])>
                            <h2 class="text-2xl font-extrabold">{{ $plan->name }}</h2>
                            <p class="mt-2 text-muted">{{ $plan->description }}</p>
                            <p class="mt-6">
                                <span class="font-display text-4xl font-extrabold">{{ \App\Support\Pricing::format($price->monthlyFor($employees), $price->currency) }}</span>
                                <span class="text-muted">/ month</span>
                            </p>
                            <p class="mt-1 text-sm text-muted">
                                for {{ $employees }} employees · from {{ \App\Support\Pricing::format($price->base_price, $price->currency) }} for up to {{ $price->included_employees }},
                                then {{ \App\Support\Pricing::format($price->extra_employee_price, $price->currency) }} each
                            </p>
                            <ul class="mt-6 flex-1 space-y-2">
                                @foreach ($plan->featureCodes() as $code)
                                    <li class="flex gap-2">
                                        <span class="text-brand-600" aria-hidden="true">✓</span>
                                        <span>{{ $features[$code]['label'] ?? $code }} @unless ($features[$code]['available'] ?? true)<span class="text-sm text-muted">(coming soon)</span>@endunless</span>
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('get-started', ['plan' => $plan->code, 'country' => $country]) }}" class="mt-8 rounded-xl bg-brand-600 py-3 text-center font-bold text-white hover:bg-brand-700">Start free trial</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.marketing>
