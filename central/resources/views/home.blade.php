<x-layouts.marketing>
    {{-- ─── Hero ─────────────────────────────────────────────────────────── --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-canvas to-white">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 pb-20 pt-16 sm:px-6 lg:grid-cols-2 lg:gap-8 lg:px-8 lg:pt-24">
            <div>
                <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.18em] text-ink/70">
                    <span class="h-2 w-2 rounded-full bg-brand-600"></span> Your connected people workspace
                </p>
                <h1 class="mt-6 text-5xl font-extrabold leading-[1.05] sm:text-6xl lg:text-7xl">
                    Your people.<br>
                    <span class="text-brand-600">One connected</span><br>
                    workspace.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-muted">
                    Hire with approvals, onboard with checklists that run themselves, and pay everyone correctly —
                    across the US, UK, Oman and Pakistan. Opsyra brings HR and payroll into one clear place.
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-5">
                    <a href="{{ route('get-started') }}" class="inline-flex items-center gap-3 rounded-xl bg-brand-600 px-7 py-4 text-base font-bold text-white shadow-lg shadow-brand-600/25 hover:bg-brand-700">
                        Start your free trial <span aria-hidden="true">↗</span>
                    </a>
                    <a href="{{ route('demo') }}" class="inline-flex items-center gap-2 border-b-2 border-ink/80 pb-0.5 font-semibold hover:border-brand-600 hover:text-brand-600">
                        Explore the live demo <span aria-hidden="true">→</span>
                    </a>
                </div>
                <p class="mt-6 flex items-center gap-2 text-sm text-muted">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M10 2l6 2.5v5c0 4-2.7 6.8-6 8.5-3.3-1.7-6-4.5-6-8.5v-5L10 2z" /><path d="M7 10l2 2 4-4" /></svg>
                    14 days free. No card needed. Your data stays in your country.
                </p>
            </div>

            {{-- Connected-modules illustration (SonariAI-style), pure HTML/SVG. --}}
            <div class="relative mx-auto aspect-square w-full max-w-[560px]" aria-hidden="true">
                <div class="dot-grid absolute inset-[8%] rounded-full"></div>
                <svg class="absolute inset-0 h-full w-full" viewBox="0 0 560 560" fill="none">
                    <circle cx="280" cy="280" r="215" stroke="#bfe2da" stroke-width="1.5" />
                    <circle cx="280" cy="280" r="150" stroke="#e3f1ee" stroke-width="1.5" />
                    <path d="M280 280 L118 132" stroke="#8fcdc0" stroke-width="1.5" stroke-dasharray="5 6" />
                    <path d="M280 280 L446 128" stroke="#8fcdc0" stroke-width="1.5" stroke-dasharray="5 6" />
                    <path d="M280 280 L112 424" stroke="#8fcdc0" stroke-width="1.5" stroke-dasharray="5 6" />
                    <path d="M280 280 L450 430" stroke="#8fcdc0" stroke-width="1.5" stroke-dasharray="5 6" />
                    <circle cx="280" cy="65" r="5" fill="#5cc4b4" />
                    <circle cx="495" cy="280" r="4" fill="#0e6e62" />
                    <circle cx="80" cy="330" r="4" fill="#5cc4b4" />
                </svg>

                <div class="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center">
                    <div class="rounded-[32px] bg-white p-4 shadow-2xl shadow-brand-600/20">
                        <x-logo :size="112" />
                    </div>
                    <p class="mt-4 font-display text-3xl font-extrabold">Opsyra</p>
                    <p class="mt-1 text-sm text-muted">People. Pay. Process.</p>
                </div>

                @php
                    $cards = [
                        ['pos' => 'left-0 top-[14%]', 'label' => 'Hire', 'text' => 'Approvals built in.', 'icon' => 'M12 12a4 4 0 100-8 4 4 0 000 8zM5 20a7 7 0 0114 0'],
                        ['pos' => 'right-0 top-[12%]', 'label' => 'Onboard', 'text' => 'Checklists that run themselves.', 'icon' => 'M9 11l3 3 8-8M20 12v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1h11'],
                        ['pos' => 'left-0 bottom-[14%]', 'label' => 'Pay', 'text' => 'Payroll for four countries.', 'icon' => 'M3 7h18v10H3zM3 10h18M7 15h3'],
                        ['pos' => 'right-0 bottom-[12%]', 'label' => 'Approve', 'text' => 'Two-level sign-off.', 'icon' => 'M5 13l4 4L19 7'],
                    ];
                @endphp
                @foreach ($cards as $i => $card)
                    <div class="absolute {{ $card['pos'] }} {{ $i % 2 ? 'float-slow-delay' : 'float-slow' }} flex w-52 items-center gap-3 rounded-2xl border border-line bg-white p-3.5 shadow-lg shadow-ink/5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-600">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $card['icon'] }}" /></svg>
                        </span>
                        <span>
                            <span class="block text-[10px] font-bold uppercase tracking-[0.14em] text-ink/50">{{ $card['label'] }}</span>
                            <span class="block text-sm font-semibold leading-snug">{{ $card['text'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─── The platform ─────────────────────────────────────────────────── --}}
    <section id="platform" class="scroll-mt-20 py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-brand-600">The platform</p>
                <h2 class="mt-3 text-4xl font-extrabold sm:text-5xl">Everything from first day to final payslip.</h2>
                <p class="mt-5 text-lg text-muted">One place for your people, the approvals around them, and the pay that follows. No spreadsheets passed between HR and finance.</p>
            </div>

            @php
                $features = [
                    ['title' => 'People and organisation', 'text' => 'Staff records, branches, departments, job titles and reporting lines — with every change logged.', 'icon' => 'M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 10a4 4 0 100-8 4 4 0 000 8zM21 20v-2a4 4 0 00-3-3.9M16 2.1a4 4 0 010 7.8'],
                    ['title' => 'Hiring and leaving, approved', 'text' => 'New starters and leavers go through a two-level approval. A manager leaving? Opsyra makes sure their team is handed over.', 'icon' => 'M9 12l2 2 4-4M12 3l7 4v5c0 4.5-3 8-7 9-4-1-7-4.5-7-9V7l7-4z'],
                    ['title' => 'Onboarding checklists', 'text' => 'Templates turn into task lists the moment someone is approved — for their manager, IT, or the new starter — each with a due date.', 'icon' => 'M9 11l3 3 8-8M20 12v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1h11'],
                    ['title' => 'Payroll, your way', 'text' => 'Your own earnings and deductions, monthly or biweekly runs, prorated joiners and leavers, and payslips your staff can query.', 'icon' => 'M3 7h18v10H3zM3 10h18M7 15h3'],
                    ['title' => 'Two-level pay approval', 'text' => 'Level-one and level-two sign-off, never by the same person. Approved pay is final — corrections go on the next payslip.', 'icon' => 'M5 13l4 4L19 7'],
                    ['title' => 'Guided setup', 'text' => 'A setup wizard walks you through company details, branches, approvers, pay and more — pre-filled for your country.', 'icon' => 'M4.5 16.5c-1.5 1.3-2 5-2 5s3.7-.5 5-2c.7-.8.7-2.1-.1-2.9a2.2 2.2 0 00-2.9-.1zM12 15l-3-3a22 22 0 012-4A12.9 12.9 0 0122 2c0 2.7-.8 7.5-6 11a22.4 22.4 0 01-4 2z'],
                ];
            @endphp

            <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as $feature)
                    <div class="rounded-2xl border border-line bg-white p-7 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-ink/5">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100 text-brand-600">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $feature['icon'] }}" /></svg>
                        </span>
                        <h3 class="mt-5 text-xl font-bold">{{ $feature['title'] }}</h3>
                        <p class="mt-2 leading-relaxed text-muted">{{ $feature['text'] }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-sm text-muted">Coming next: attendance and leave, HR dashboards, staff ID cards and bulk import.</p>
        </div>
    </section>

    {{-- ─── How it works ─────────────────────────────────────────────────── --}}
    <section id="how-it-works" class="scroll-mt-20 bg-canvas py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-brand-600">How it works</p>
                <h2 class="mt-3 text-4xl font-extrabold sm:text-5xl">Up and running in an afternoon.</h2>
            </div>
            <ol class="mt-14 grid gap-5 md:grid-cols-3">
                @foreach ([
                    ['Start your trial', 'Tell us your company name, country and size. Your workspace is ready in a minute — no card needed.'],
                    ['Follow the setup wizard', 'Company details, branches, approvers, pay components. Sensible defaults for your country are already filled in.'],
                    ['Run HR and payroll', 'Add your people, approve starters and leavers, and run your first payroll with confidence.'],
                ] as $i => [$title, $text])
                    <li class="rounded-2xl border border-line bg-white p-7">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 font-display font-bold text-white">{{ $i + 1 }}</span>
                        <h3 class="mt-5 text-xl font-bold">{{ $title }}</h3>
                        <p class="mt-2 leading-relaxed text-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ─── Countries ────────────────────────────────────────────────────── --}}
    <section id="countries" class="scroll-mt-20 py-24">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-brand-600">Built for your country</p>
                <h2 class="mt-3 text-4xl font-extrabold sm:text-5xl">Local by design. Your data stays home.</h2>
                <p class="mt-5 text-lg text-muted">
                    Each country runs on its own Opsyra deployment, so your employees' data stays in your country.
                    Currencies, pay items and leaving reasons start out right for where you are.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                @foreach ([['United States', 'USD', '🇺🇸'], ['United Kingdom', 'GBP', '🇬🇧'], ['Oman', 'OMR', '🇴🇲'], ['Pakistan', 'PKR', '🇵🇰']] as [$name, $currency, $flag])
                    <div class="rounded-2xl border border-line bg-white p-6">
                        <span class="text-3xl" aria-hidden="true">{{ $flag }}</span>
                        <p class="mt-3 font-display text-lg font-bold">{{ $name }}</p>
                        <p class="text-sm text-muted">Paid in {{ $currency }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─── Pricing teaser ───────────────────────────────────────────────── --}}
    <section id="pricing" class="scroll-mt-20 bg-canvas py-24">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-brand-600">Pricing</p>
            <h2 class="mt-3 text-4xl font-extrabold sm:text-5xl">Try everything free for 14 days.</h2>
            <p class="mt-5 text-lg text-muted">Every feature, no card, no commitment. Simple per-employee plans for each country are on their way.</p>
            <div class="mt-9 flex flex-wrap items-center justify-center gap-5">
                <a href="{{ route('get-started') }}" class="inline-flex items-center gap-3 rounded-xl bg-brand-600 px-7 py-4 font-bold text-white shadow-lg shadow-brand-600/25 hover:bg-brand-700">
                    Start your free trial <span aria-hidden="true">↗</span>
                </a>
                <a href="{{ route('pricing') }}" class="font-semibold text-brand-600 hover:text-brand-700">See pricing →</a>
            </div>
        </div>
    </section>

    {{-- ─── Demo band ────────────────────────────────────────────────────── --}}
    <section class="py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-start justify-between gap-8 rounded-3xl bg-brand-800 p-10 text-white md:flex-row md:items-center lg:p-14">
                <div>
                    <h2 class="text-3xl font-extrabold sm:text-4xl">See it with real-looking data.</h2>
                    <p class="mt-3 max-w-xl text-white/75">Step into our demo company — staff, approvals, checklists and payslips already in place. It resets every night.</p>
                </div>
                <a href="{{ route('demo') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-white px-7 py-4 font-bold text-brand-800 hover:bg-brand-100">
                    Explore the demo <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>
</x-layouts.marketing>
