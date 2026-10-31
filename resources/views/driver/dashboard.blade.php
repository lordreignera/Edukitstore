@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'driver.dashboard', 'pattern' => 'driver.dashboard', 'icon' => 'home'],
        ['label' => 'My Deliveries', 'route' => 'driver.deliveries.index', 'pattern' => 'driver.deliveries.*', 'icon' => 'drivers', 'badge' => $stats['ready'] ?: null],
    ];
@endphp
<x-portal-layout title="Delivery Dashboard" portal-name="Delivery Partner" :navigation="$navigation" theme="orange">
    <x-slot name="header"><div><h1 class="text-2xl font-extrabold text-[#071d4f]">Dashboard</h1><p class="mt-1 text-sm text-slate-500">Welcome back, {{ str($driver->name)->before(' ') }}. Manage your availability and assigned trips.</p></div></x-slot>
    <div class="px-4 py-6 sm:px-6 lg:px-8"><div class="mx-auto max-w-[1500px] space-y-5">
        @if (session('status'))<div class="flex items-center gap-3 rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-800"><x-ui.icon name="check" size="size-5" />{{ session('status') }}</div>@endif
        <x-dashboard-guide
            eyebrow="Delivery workflow"
            title="How to complete an assigned trip"
            intro="Only start a journey when the order is paid, stock is ready and no payment exception is shown. The customer completes the final receipt confirmation."
            :steps="[
                ['title' => 'Set your availability', 'description' => 'Mark yourself available when you can accept trips, and add a note when you need to explain an unavailable status.'],
                ['title' => 'Open My Deliveries', 'description' => 'Use the ready and active filters to find assigned trips and check the customer, destination and order status.', 'route' => 'driver.deliveries.index', 'action' => 'Open deliveries'],
                ['title' => 'Start the journey', 'description' => 'Start only after payment is verified and the trip is not under a payment exception.'],
                ['title' => 'Record arrival', 'description' => 'When you reach the destination, mark that you have reached and add useful delivery notes.'],
                ['title' => 'Hand over and wait for confirmation', 'description' => 'Let the customer inspect the order and confirm receipt. You do not mark the final customer receipt yourself.'],
            ]"
            :notes="[
                'If payment is pending or under review, wait for the administrator to resolve it instead of starting the trip.',
                'Assigned trips remain visible even when you switch your availability to unavailable.',
            ]"
        />
        @include('driver.partials.dashboard.availability-panel')
        @include('driver.partials.dashboard.stat-cards')
        <div class="grid gap-5 xl:grid-cols-[1.45fr_1fr]">@include('driver.partials.dashboard.active-deliveries') @include('driver.partials.dashboard.recent-completed')</div>
    </div></div>
</x-portal-layout>
