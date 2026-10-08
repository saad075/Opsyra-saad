<x-layouts.marketing title="Start your free trial">
    <section class="bg-gradient-to-b from-canvas to-white">
        <div class="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-5 lg:px-8 lg:py-20">
            <div class="lg:col-span-2">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-brand-600">Free for 14 days</p>
                <h1 class="mt-3 text-4xl font-extrabold sm:text-5xl">Start your free trial.</h1>
                <p class="mt-5 text-lg text-muted">Every feature, no card, no commitment. Your workspace is ready in about a minute.</p>
                <ul class="mt-8 space-y-4">
                    @foreach ([
                        ['We create your workspace', 'In your country, so your employees’ data stays there.'],
                        ['You get an email', 'With your company code and a link to set your password.'],
                        ['The setup wizard takes over', 'Company details, branches, approvers and pay — pre-filled for your country.'],
                    ] as $i => [$title, $text])
                        <li class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 font-display text-sm font-bold text-white">{{ $i + 1 }}</span>
                            <span><strong class="block">{{ $title }}</strong><span class="text-muted">{{ $text }}</span></span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <form method="POST" action="{{ route('get-started') }}" class="rounded-3xl border border-line bg-white p-7 shadow-xl shadow-ink/5 sm:p-9 lg:col-span-3" novalidate>
                @csrf
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Please check the highlighted fields.</div>
                @endif

                <h2 class="text-xl font-bold">Your company</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="company_name" class="text-sm font-semibold">Company name</label>
                        <input id="company_name" name="company_name" value="{{ old('company_name') }}" required autocomplete="organization" class="mt-1 w-full rounded-xl border border-line px-4 py-2.5 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        @error('company_name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="client_code" class="text-sm font-semibold">Company code</label>
                        <input id="client_code" name="client_code" value="{{ old('client_code') }}" required autocapitalize="off" spellcheck="false" placeholder="e.g. acme" class="mt-1 w-full rounded-xl border border-line px-4 py-2.5 lowercase focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        <p id="code-hint" class="mt-1 text-sm text-muted">Your team types this to sign in. Lowercase letters, numbers and hyphens.</p>
                        @error('client_code') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="country" class="text-sm font-semibold">Country</label>
                        <select id="country" name="country" required class="mt-1 w-full rounded-xl border border-line bg-white px-4 py-2.5 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="" disabled @selected(! old('country', $selectedCountry))>Choose…</option>
                            @foreach ($countries as $code => $info)
                                <option value="{{ $code }}" @selected(old('country', $selectedCountry) === $code)>{{ $info['name'] }}</option>
                            @endforeach
                        </select>
                        @error('country') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="employees" class="text-sm font-semibold">Number of employees</label>
                        <input id="employees" name="employees" type="number" min="1" value="{{ old('employees') }}" required class="mt-1 w-full rounded-xl border border-line px-4 py-2.5 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        @error('employees') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>

                <h2 class="mt-9 text-xl font-bold">About you</h2>
                <p class="text-sm text-muted">You’ll be the workspace administrator.</p>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="admin_name" class="text-sm font-semibold">Your name</label>
                        <input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required autocomplete="name" class="mt-1 w-full rounded-xl border border-line px-4 py-2.5 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        @error('admin_name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="admin_email" class="text-sm font-semibold">Work email</label>
                        <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required autocomplete="email" class="mt-1 w-full rounded-xl border border-line px-4 py-2.5 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        @error('admin_email') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="phone" class="text-sm font-semibold">Phone <span class="font-normal text-muted">(optional)</span></label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" class="mt-1 w-full rounded-xl border border-line px-4 py-2.5 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    </div>
                </div>

                @if ($plans->isNotEmpty())
                    <input type="hidden" name="plan" value="{{ old('plan', $selectedPlan) }}">
                @endif

                <label class="mt-8 flex items-start gap-3 text-sm">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 h-4 w-4 accent-brand-600">
                    <span>I agree to the terms of service and privacy policy.</span>
                </label>
                @error('terms') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror

                <button class="mt-7 w-full rounded-xl bg-brand-600 py-4 text-lg font-bold text-white shadow-lg shadow-brand-600/25 hover:bg-brand-700">Create my workspace</button>
                <p class="mt-4 text-center text-sm text-muted">Already have a workspace? <a href="{{ route('login') }}" class="font-semibold text-brand-600">Log in</a></p>
            </form>
        </div>
    </section>

    <script>
        // Live availability check for the company code (the server re-checks on submit).
        (() => {
            const input = document.getElementById('client_code');
            const hint = document.getElementById('code-hint');
            const original = hint.textContent;
            let timer;
            input.addEventListener('input', () => {
                clearTimeout(timer);
                input.value = input.value.toLowerCase().replace(/\s+/g, '-');
                if (input.value.length < 3) { hint.textContent = original; hint.className = 'mt-1 text-sm text-muted'; return; }
                timer = setTimeout(async () => {
                    try {
                        const res = await fetch(`{{ route('get-started.check-code') }}?code=${encodeURIComponent(input.value)}`, { headers: { Accept: 'application/json' } });
                        const data = await res.json();
                        hint.textContent = data.available ? '✓ Available' : data.message;
                        hint.className = 'mt-1 text-sm ' + (data.available ? 'text-brand-600 font-semibold' : 'text-red-700');
                    } catch { hint.textContent = original; }
                }, 350);
            });
        })();
    </script>
</x-layouts.marketing>
