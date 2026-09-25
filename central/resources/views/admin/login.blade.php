<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Sign in · Opsyra Central</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-b from-brand-100 to-canvas px-4">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex items-center justify-center gap-2">
            <x-logo :size="34" />
            <span class="font-display text-xl font-bold">Opsyra <span class="text-muted">Central</span></span>
        </div>
        <form method="POST" action="{{ route('admin.login') }}" class="rounded-2xl border border-line bg-white p-7 shadow-sm">
            @csrf
            <h1 class="text-2xl font-extrabold">BrainStormers sign-in</h1>
            <p class="mt-1 text-sm text-muted">For BrainStormers staff only. Companies sign in from the main site.</p>

            @error('email')
                <p class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</p>
            @enderror

            <label class="mt-5 block text-sm font-semibold" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="mt-1 w-full rounded-lg border border-line px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">

            <label class="mt-4 block text-sm font-semibold" for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-1 w-full rounded-lg border border-line px-3 py-2 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200">

            <button class="mt-6 w-full rounded-lg bg-brand-600 py-2.5 font-bold text-white hover:bg-brand-700">Sign in</button>
        </form>
    </div>
</body>
</html>
