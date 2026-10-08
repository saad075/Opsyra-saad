<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · Opsyra' : 'Opsyra — HR, onboarding and payroll in one connected workspace' }}</title>
    <meta name="description" content="{{ $description ?? 'Opsyra brings hiring approvals, onboarding and offboarding checklists, and payroll for the US, UK, Oman and Pakistan into one workspace.' }}">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body>
    <header class="sticky top-0 z-40 border-b border-line/70 bg-white/85 backdrop-blur">
        <nav class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8" aria-label="Main">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <x-logo :size="34" />
                <span class="font-display text-xl font-bold tracking-tight">Opsyra</span>
            </a>

            <div class="hidden items-center gap-8 text-[15px] font-semibold text-ink/80 md:flex">
                <a href="{{ route('home') }}#platform" class="hover:text-brand-600">The platform</a>
                <a href="{{ route('home') }}#how-it-works" class="hover:text-brand-600">How it works</a>
                <a href="{{ route('home') }}#countries" class="hover:text-brand-600">Countries</a>
                <a href="{{ route('pricing') }}" class="hover:text-brand-600">Pricing</a>
                <a href="{{ route('demo') }}" class="hover:text-brand-600">Demo</a>
            </div>

            <div class="hidden items-center gap-3 md:flex">
                <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-[15px] font-semibold text-ink hover:text-brand-600">Log in</a>
                <a href="{{ route('get-started') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-[15px] font-semibold text-white shadow-sm shadow-brand-600/30 hover:bg-brand-700">
                    Get started <span aria-hidden="true">↗</span>
                </a>
            </div>

            {{-- Mobile menu: no JavaScript needed. --}}
            <details class="relative md:hidden">
                <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg border border-line" aria-label="Menu">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 6h14M3 10h14M3 14h14" /></svg>
                </summary>
                <div class="absolute right-0 mt-2 w-64 rounded-2xl border border-line bg-white p-3 shadow-xl">
                    <a href="{{ route('home') }}#platform" class="block rounded-lg px-3 py-2 font-semibold hover:bg-canvas">The platform</a>
                    <a href="{{ route('home') }}#how-it-works" class="block rounded-lg px-3 py-2 font-semibold hover:bg-canvas">How it works</a>
                    <a href="{{ route('home') }}#countries" class="block rounded-lg px-3 py-2 font-semibold hover:bg-canvas">Countries</a>
                    <a href="{{ route('pricing') }}" class="block rounded-lg px-3 py-2 font-semibold hover:bg-canvas">Pricing</a>
                    <a href="{{ route('demo') }}" class="block rounded-lg px-3 py-2 font-semibold hover:bg-canvas">Demo</a>
                    <hr class="my-2 border-line">
                    <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 font-semibold hover:bg-canvas">Log in</a>
                    <a href="{{ route('get-started') }}" class="mt-1 block rounded-lg bg-brand-600 px-3 py-2 text-center font-semibold text-white">Get started</a>
                </div>
            </details>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-line bg-canvas">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-4 lg:px-8">
            <div class="md:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <x-logo :size="30" />
                    <span class="font-display text-lg font-bold">Opsyra</span>
                </a>
                <p class="mt-4 max-w-sm text-muted">HR, onboarding and payroll in one connected workspace. Built by BrainStormers.</p>
            </div>
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-ink/60">Product</h3>
                <ul class="mt-4 space-y-2 text-muted">
                    <li><a href="{{ route('home') }}#platform" class="hover:text-brand-600">The platform</a></li>
                    <li><a href="{{ route('home') }}#how-it-works" class="hover:text-brand-600">How it works</a></li>
                    <li><a href="{{ route('demo') }}" class="hover:text-brand-600">Demo</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-ink/60">Account</h3>
                <ul class="mt-4 space-y-2 text-muted">
                    <li><a href="{{ route('login') }}" class="hover:text-brand-600">Log in</a></li>
                    <li><a href="{{ route('get-started') }}" class="hover:text-brand-600">Start a free trial</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-line">
            <p class="mx-auto max-w-7xl px-4 py-6 text-sm text-muted sm:px-6 lg:px-8">© {{ date('Y') }} BrainStormers. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
