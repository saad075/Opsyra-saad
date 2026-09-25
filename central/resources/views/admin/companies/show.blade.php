<x-layouts.admin :title="$company->name">
    <a href="{{ route('admin.companies') }}" class="text-sm font-semibold text-muted hover:text-ink">← Companies</a>

    <div class="mt-3 flex flex-wrap items-center gap-3">
        <h1 class="text-3xl font-extrabold">{{ $company->name }}</h1>
        <x-status-badge :status="$company->status" class="text-sm" />
        @if ($company->is_demo) <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-bold">Public demo</span> @endif
    </div>
    <p class="mt-1 text-muted">Code <strong class="text-ink">{{ $company->client_code }}</strong> · {{ $company->countryName() }} · joined {{ $company->created_at->format('j M Y') }}</p>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-line bg-white p-5">
                    <p class="text-sm font-semibold text-muted">Plan</p>
                    <p class="mt-1 font-display text-xl font-bold">{{ $company->plan?->name ?? '—' }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-white p-5">
                    <p class="text-sm font-semibold text-muted">Active staff</p>
                    <p class="mt-1 font-display text-xl font-bold">{{ number_format($company->billableEmployees()) }}</p>
                    <p class="text-xs text-muted">{{ $company->usage_reported_at ? 'reported '.$company->usage_reported_at->diffForHumans() : 'from sign-up, not yet reported' }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-white p-5">
                    <p class="text-sm font-semibold text-muted">Monthly price</p>
                    <p class="mt-1 font-display text-xl font-bold">{{ ($price = $company->monthlyPrice()) !== null ? \App\Support\Pricing::format($price, $company->currency()) : '—' }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-line bg-white p-6">
                <h2 class="font-bold">Details</h2>
                <dl class="mt-4 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-muted">Administrator</dt><dd class="font-semibold">{{ $company->admin_name }}</dd></div>
                    <div><dt class="text-muted">Email</dt><dd class="font-semibold"><a href="mailto:{{ $company->admin_email }}" class="hover:text-brand-600">{{ $company->admin_email }}</a></dd></div>
                    <div><dt class="text-muted">Phone</dt><dd class="font-semibold">{{ $company->phone ?? '—' }}</dd></div>
                    <div><dt class="text-muted">Staff at sign-up</dt><dd class="font-semibold">{{ $company->expected_employees ?? '—' }}</dd></div>
                    <div><dt class="text-muted">Trial ends</dt><dd class="font-semibold">{{ $company->trial_ends_at?->format('j M Y') ?? '—' }}</dd></div>
                    <div>
                        <dt class="text-muted">Country copy</dt>
                        <dd class="font-semibold">
                            @if ($company->sync_error) <span class="text-amber-800">Out of date — {{ $company->sync_error }}</span>
                            @elseif ($company->synced_at) Up to date ({{ $company->synced_at->diffForHumans() }})
                            @else — @endif
                        </dd>
                    </div>
                </dl>
                @if ($company->provision_error)
                    <p class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">Setup error: {{ $company->provision_error }}</p>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="space-y-4">
            @if (in_array($company->status, ['provisioning_failed']) || $company->sync_error)
                <form method="POST" action="{{ route('admin.companies.retry', $company) }}" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                    @csrf
                    <p class="text-sm font-semibold text-amber-900">{{ $company->status === 'provisioning_failed' ? 'The workspace wasn’t created.' : 'The country copy is out of date.' }}</p>
                    <button class="mt-3 w-full rounded-lg bg-amber-600 py-2 font-semibold text-white hover:bg-amber-700">{{ $company->status === 'provisioning_failed' ? 'Retry setup' : 'Retry sync' }}</button>
                </form>
            @endif

            @unless (in_array($company->status, ['provisioning', 'provisioning_failed']))
                <form method="POST" action="{{ route('admin.companies.extend-trial', $company) }}" class="rounded-2xl border border-line bg-white p-5">
                    @csrf
                    <label class="text-sm font-semibold">Extend trial by
                        <select name="days" class="mt-1 block w-full rounded-lg border border-line px-3 py-2 font-normal">
                            @foreach ([7, 14, 30] as $days) <option value="{{ $days }}">{{ $days }} days</option> @endforeach
                        </select>
                    </label>
                    <button class="mt-3 w-full rounded-lg border border-line py-2 font-semibold hover:bg-canvas">Extend trial</button>
                </form>

                <form method="POST" action="{{ route('admin.companies.plan', $company) }}" class="rounded-2xl border border-line bg-white p-5">
                    @csrf
                    <label class="text-sm font-semibold">Plan
                        <select name="plan_id" class="mt-1 block w-full rounded-lg border border-line px-3 py-2 font-normal">
                            @foreach ($plans as $plan) <option value="{{ $plan->id }}" @selected($plan->id === $company->plan_id)>{{ $plan->name }}</option> @endforeach
                        </select>
                    </label>
                    <button class="mt-3 w-full rounded-lg border border-line py-2 font-semibold hover:bg-canvas">Change plan</button>
                </form>

                <form method="POST" action="{{ route('admin.companies.status', $company) }}" class="rounded-2xl border border-line bg-white p-5">
                    @csrf
                    <label class="text-sm font-semibold">Status
                        <select name="status" class="mt-1 block w-full rounded-lg border border-line px-3 py-2 font-normal">
                            @foreach (['trial' => 'Trial', 'active' => 'Active (paying)', 'past_due' => 'Past due', 'suspended' => 'Suspended — blocks sign-in', 'cancelled' => 'Cancelled'] as $value => $label)
                                <option value="{{ $value }}" @selected($company->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="mt-3 w-full rounded-lg border border-line py-2 font-semibold hover:bg-canvas">Update status</button>
                    <p class="mt-2 text-xs text-muted">Suspending signs everyone at the company out immediately.</p>
                </form>
            @endunless
        </div>
    </div>
</x-layouts.admin>
