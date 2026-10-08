<x-layouts.marketing :title="$title">
    <section class="bg-gradient-to-b from-canvas to-white">
        <div class="mx-auto max-w-2xl px-4 py-28 text-center sm:px-6">
            <x-logo :size="56" class="mx-auto" />
            <h1 class="mt-8 text-4xl font-extrabold sm:text-5xl">{{ $heading }}</h1>
            <p class="mt-5 text-lg text-muted">{{ $text }}</p>
            <a href="{{ route('home') }}" class="mt-10 inline-flex items-center gap-2 font-semibold text-brand-600 hover:text-brand-700">← Back to home</a>
        </div>
    </section>
</x-layouts.marketing>
