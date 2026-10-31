@php
    $navigation = config('portals.supplier_navigation');
@endphp

<x-portal-layout title="Supplier Dashboard" portal-name="Supplier Portal" :navigation="$navigation" search-action="{{ route('supplier.stock.index') }}" search-placeholder="Search supplied products...">
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div><h1 class="text-2xl font-extrabold text-[#071d4f]">Dashboard</h1><p class="mt-1 text-sm text-slate-500">Welcome, {{ $supplier->business_name }}. Here is your supply activity.</p></div>
            <span class="inline-flex w-fit items-center gap-2 rounded-md border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700"><x-ui.icon name="check" size="size-4" />Approved supplier</span>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8"><div class="mx-auto max-w-[1500px] space-y-5">
        <x-dashboard-guide
            eyebrow="Supplier workflow"
            title="How to supply through EduKit"
            intro="Submit products or stock for review, wait for approval, then monitor the batches that EduKit receives and the earnings released after delivery."
            :steps="[
                ['title' => 'Submit a product or stock', 'description' => 'Use My Products to choose an existing catalogue product or propose a new one, then enter your price and quantity.', 'route' => 'supplier.offers.create', 'action' => 'Submit an offer'],
                ['title' => 'Wait for approval', 'description' => 'New products and stock are pending until the EduKit team reviews them. Rejected offers show the review note.'],
                ['title' => 'Restock approved products', 'description' => 'For an approved product, submit additional quantity from My Products. The new quantity is reviewed before it is available.'],
                ['title' => 'Check received batches', 'description' => 'Use Stock Records to confirm quantities recorded by the EduKit warehouse and see what remains available.', 'route' => 'supplier.stock.index', 'action' => 'View stock records'],
                ['title' => 'Review earnings', 'description' => 'Sales, supplier payable and settlement status are shown separately; earnings are released after the customer receives the order.', 'route' => 'supplier.earnings.index', 'action' => 'View earnings'],
            ]"
            :notes="[
                'Your customer price and availability are controlled through the EduKit approval process.',
                'A supplier account must be approved and active before this workspace can be used.',
            ]"
        />
        @include('supplier.partials.dashboard.account-summary')
        @include('supplier.partials.dashboard.stat-cards')
        <div class="grid gap-5 xl:grid-cols-[1.35fr_1fr]">
            @include('supplier.partials.dashboard.recent-batches')
            @include('supplier.partials.dashboard.supplied-products')
        </div>
    </div></div>
</x-portal-layout>
