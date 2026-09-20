@php
    $navigation = config('portals.supplier_navigation');
@endphp

<x-portal-layout title="Supplier Dashboard" portal-name="Supplier Portal" :navigation="$navigation" search-action="{{ route('supplier.stock.index') }}" search-placeholder="Search supplied products...">
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div><h1 class="text-2xl font-extrabold text-[#071d4f]">Dashboard</h1><p class="mt-1 text-sm text-slate-500">Welcome, {{ $supplier->business_name }}. Here is your supply activity.</p></div>
            <span class="inline-flex w-fit items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700"><x-ui.icon name="check" size="size-4" />Approved supplier</span>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8"><div class="mx-auto max-w-[1500px] space-y-5">
        @include('supplier.partials.dashboard.account-summary')
        @include('supplier.partials.dashboard.stat-cards')
        <div class="grid gap-5 xl:grid-cols-[1.35fr_1fr]">
            @include('supplier.partials.dashboard.recent-batches')
            @include('supplier.partials.dashboard.supplied-products')
        </div>
    </div></div>
</x-portal-layout>
