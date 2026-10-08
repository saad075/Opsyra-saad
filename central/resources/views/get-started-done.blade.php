<x-layouts.marketing title="Welcome to Opsyra">
    <section class="bg-gradient-to-b from-canvas to-white">
        <div class="mx-auto max-w-2xl px-4 py-24 text-center sm:px-6">
            @if ($company->status === 'trial')
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 text-3xl text-brand-600">✓</div>
                <h1 class="mt-8 text-4xl font-extrabold sm:text-5xl">Your workspace is ready.</h1>
                <p class="mt-5 text-lg text-muted">
                    We’ve emailed <strong class="text-ink">{{ $company->admin_email }}</strong> a link to set your password.
                    Your company code is <strong class="text-ink">{{ $company->client_code }}</strong>.
                </p>
                <p class="mt-3 text-muted">Your free trial runs until {{ $company->trial_ends_at->format('j F Y') }}.</p>
                <a href="{{ route('login', ['code' => $company->client_code]) }}" class="mt-10 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-7 py-4 font-bold text-white hover:bg-brand-700">Go to sign in →</a>
            @else
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-3xl">⏳</div>
                <h1 class="mt-8 text-4xl font-extrabold sm:text-5xl">We’re finishing your setup.</h1>
                <p class="mt-5 text-lg text-muted">
                    Your details are saved and our team has been alerted. We’ll email
                    <strong class="text-ink">{{ $company->admin_email }}</strong> as soon as <strong class="text-ink">{{ $company->name }}</strong> is ready — usually within the hour.
                </p>
            @endif
        </div>
    </section>
</x-layouts.marketing>
