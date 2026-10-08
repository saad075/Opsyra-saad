<x-layouts.marketing title="Live demo" description="Explore Opsyra with a sample company — staff, approvals, onboarding checklists and payroll already in place. Resets every night.">
    <section class="bg-gradient-to-b from-canvas to-white">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-brand-600">Live demo</p>
                <h1 class="mt-3 text-4xl font-extrabold sm:text-5xl lg:text-6xl">Step inside a company that already runs on Opsyra.</h1>
                <p class="mt-6 text-lg leading-relaxed text-muted">
                    Demo Company has three offices and more than twenty people — with a new hire waiting for approval,
                    someone leaving next month, onboarding checklists under way, and four months of payroll.
                    Click around, approve things, run payroll. It all resets every night.
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-5">
                    @if ($appUrl)
                        <a href="{{ $appUrl }}" class="inline-flex items-center gap-3 rounded-xl bg-brand-600 px-7 py-4 text-base font-bold text-white shadow-lg shadow-brand-600/25 hover:bg-brand-700">
                            Enter the demo <span aria-hidden="true">→</span>
                        </a>
                    @endif
                    <a href="{{ route('get-started') }}" class="inline-flex items-center gap-2 border-b-2 border-ink/80 pb-0.5 font-semibold hover:border-brand-600 hover:text-brand-600">
                        Or start your own free trial
                    </a>
                </div>

                <div class="mt-8 max-w-md rounded-2xl border border-line bg-white p-5 text-sm">
                    <p class="font-semibold">Sign-in details (filled in for you)</p>
                    <dl class="mt-3 grid grid-cols-[auto,1fr] gap-x-4 gap-y-1.5">
                        <dt class="text-muted">Company code</dt><dd class="font-mono">demo</dd>
                        <dt class="text-muted">Email</dt><dd class="font-mono">{{ $email }}</dd>
                        <dt class="text-muted">Password</dt><dd class="font-mono">{{ $password }}</dd>
                    </dl>
                    <p class="mt-3 text-muted">Shared by every visitor, so please don’t enter anything real.</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['HR dashboard', 'Headcount by branch, joiners and leavers, approvals waiting, overdue onboarding tasks.'],
                    ['Payroll dashboard', 'Four pay runs, payslips awaiting approval, cheques to number, a salary query to answer.'],
                    ['Approvals', 'A new hire and a leaver waiting for sign-off — approve them and watch the checklists appear.'],
                    ['Setup, done', 'See how a finished setup looks: branches, approvers, pay components and leaving reasons.'],
                ] as [$title, $text])
                    <div class="rounded-2xl border border-line bg-white p-6">
                        <h2 class="text-lg font-bold">{{ $title }}</h2>
                        <p class="mt-2 text-muted">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.marketing>
