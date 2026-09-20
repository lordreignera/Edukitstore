@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'driver.dashboard', 'pattern' => 'driver.dashboard', 'icon' => 'home'],
        ['label' => 'My Deliveries', 'route' => 'driver.deliveries.index', 'pattern' => 'driver.deliveries.*', 'icon' => 'drivers', 'badge' => $stats['ready'] ?: null],
    ];
@endphp
<x-portal-layout title="Delivery Dashboard" portal-name="Delivery Partner" :navigation="$navigation" theme="violet">
    <x-slot name="header"><div><h1 class="text-2xl font-extrabold text-[#071d4f]">Dashboard</h1><p class="mt-1 text-sm text-slate-500">Welcome back, {{ str($driver->name)->before(' ') }}. Manage your availability and assigned trips.</p></div></x-slot>
    <div class="px-4 py-6 sm:px-6 lg:px-8"><div class="mx-auto max-w-[1500px] space-y-5">
        @if (session('status'))<div class="flex items-center gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><x-ui.icon name="check" size="size-5" />{{ session('status') }}</div>@endif
        @include('driver.partials.dashboard.availability-panel')
        @include('driver.partials.dashboard.stat-cards')
        <div class="grid gap-5 xl:grid-cols-[1.45fr_1fr]">@include('driver.partials.dashboard.active-deliveries') @include('driver.partials.dashboard.recent-completed')</div>
    </div></div>
</x-portal-layout>
