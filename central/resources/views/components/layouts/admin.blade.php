<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? 'Admin' }} · Opsyra Central</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-canvas">
    <header class="border-b border-line bg-white">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex items-center gap-8">
                <a href="{{ route('admin.home') }}" class="flex items-center gap-2">
                    <x-logo :size="28" />
                    <span class="font-display font-bold">Opsyra <span class="font-semibold text-muted">Central</span></span>
                </a>
                <nav class="flex gap-1 text-[15px] font-semibold">
                    <a href="{{ route('admin.pricing') }}" @class(['rounded-lg px-3 py-1.5', 'bg-brand-100 text-brand-700' => request()->routeIs('admin.pricing'), 'text-ink/70 hover:text-ink' => ! request()->routeIs('admin.pricing')])>Pricing</a>
                    <a href="{{ route('admin.plans') }}" @class(['rounded-lg px-3 py-1.5', 'bg-brand-100 text-brand-700' => request()->routeIs('admin.plans'), 'text-ink/70 hover:text-ink' => ! request()->routeIs('admin.plans')])>Plans</a>
                </nav>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}" class="flex items-center gap-3 text-sm">
                @csrf
                <span class="hidden text-muted sm:inline">{{ auth()->user()->name }}</span>
                <button class="rounded-lg border border-line px-3 py-1.5 font-semibold hover:bg-canvas">Sign out</button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        @if (session('status'))
            <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 font-semibold text-brand-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
