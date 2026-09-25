@props(['status'])
@php
    $styles = [
        'trial' => 'bg-sky-100 text-sky-800',
        'active' => 'bg-brand-100 text-brand-700',
        'past_due' => 'bg-amber-100 text-amber-900',
        'suspended' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-200 text-gray-700',
        'provisioning' => 'bg-gray-100 text-gray-700',
        'provisioning_failed' => 'bg-red-100 text-red-800',
    ];
    $labels = ['past_due' => 'Past due', 'provisioning' => 'Setting up', 'provisioning_failed' => 'Setup failed'];
@endphp
<span {{ $attributes->class(['inline-block rounded-full px-2.5 py-0.5 text-xs font-bold', $styles[$status] ?? 'bg-gray-100 text-gray-700']) }}>
    {{ $labels[$status] ?? ucfirst($status) }}
</span>
