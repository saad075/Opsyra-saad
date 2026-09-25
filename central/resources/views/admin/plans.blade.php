<x-layouts.admin title="Plans">
    <h1 class="text-3xl font-extrabold">Plans</h1>
    <p class="mt-1 text-muted">Names, descriptions and the features each plan unlocks. Prices are set per country on the Pricing tab.</p>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        @foreach ($plans as $plan)
            <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="flex flex-col rounded-2xl border border-line bg-white p-6">
                @csrf
                <label class="text-sm font-semibold" for="name-{{ $plan->id }}">Name</label>
                <input id="name-{{ $plan->id }}" name="name" value="{{ $plan->name }}" required class="mt-1 rounded-lg border border-line px-3 py-2 font-display font-bold focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">

                <label class="mt-4 text-sm font-semibold" for="desc-{{ $plan->id }}">Description</label>
                <textarea id="desc-{{ $plan->id }}" name="description" rows="2" class="mt-1 rounded-lg border border-line px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ $plan->description }}</textarea>

                <fieldset class="mt-5">
                    <legend class="text-sm font-semibold">Features</legend>
                    <div class="mt-2 space-y-2">
                        @foreach ($features as $code => $feature)
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" name="features[]" value="{{ $code }}" @checked(in_array($code, $plan->featureCodes(), true)) class="mt-0.5 accent-brand-600">
                                <span>
                                    {{ $feature['label'] }}
                                    @unless ($feature['available'])
                                        <span class="ml-1 rounded bg-canvas px-1.5 py-0.5 text-xs text-muted">coming soon</span>
                                    @endunless
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="mt-5 flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked($plan->is_active) class="accent-brand-600"> Offered
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        Order <input type="number" name="sort_order" value="{{ $plan->sort_order }}" min="0" class="w-16 rounded-lg border border-line px-2 py-1">
                    </label>
                </div>

                <button class="mt-6 rounded-lg bg-brand-600 py-2.5 font-bold text-white hover:bg-brand-700">Save {{ $plan->name }}</button>
            </form>
        @endforeach
    </div>
</x-layouts.admin>
