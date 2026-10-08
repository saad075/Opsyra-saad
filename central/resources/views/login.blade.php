<x-layouts.marketing title="Log in">
    <section class="bg-gradient-to-b from-canvas to-white">
        <div class="mx-auto max-w-md px-4 py-24 sm:px-6">
            <div class="rounded-3xl border border-line bg-white p-8 shadow-xl shadow-ink/5">
                <x-logo :size="44" />
                <h1 class="mt-6 text-3xl font-extrabold">Log in to your workspace</h1>
                <p class="mt-2 text-muted">Enter your company code. We’ll take you to your company’s sign-in page.</p>

                <form method="POST" action="{{ route('login') }}" class="mt-7">
                    @csrf
                    <label for="client_code" class="text-sm font-semibold">Company code</label>
                    <input id="client_code" name="client_code" value="{{ old('client_code', $code) }}" required autofocus autocapitalize="off" autocomplete="organization"
                        placeholder="e.g. acme"
                        class="mt-1 w-full rounded-xl border border-line px-4 py-3 text-lg focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    @error('client_code')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <button class="mt-6 w-full rounded-xl bg-brand-600 py-3.5 font-bold text-white hover:bg-brand-700">Continue →</button>
                </form>

                <div class="mt-8 space-y-2 border-t border-line pt-6 text-sm text-muted">
                    <p>Don’t know your code? Your administrator received it when your company joined Opsyra.</p>
                    <p>New to Opsyra? <a href="{{ route('get-started') }}" class="font-semibold text-brand-600 hover:text-brand-700">Start a free trial</a> or <a href="{{ route('demo') }}" class="font-semibold text-brand-600 hover:text-brand-700">explore the demo</a>.</p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.marketing>
