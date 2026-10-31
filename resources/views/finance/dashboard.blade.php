@php
    $navigation = [
        ['label' => 'Profit report', 'route' => 'finance.dashboard', 'pattern' => 'finance.dashboard', 'icon' => 'chart'],
    ];
    $warehouse = $summary->get('edukit');
    $supplier = $summary->get('supplier');
@endphp

<x-portal-layout title="Finance Dashboard" portal-name="Finance Portal" :navigation="$navigation" theme="orange">
    <x-slot name="header">
        <div><h1 class="text-2xl font-extrabold text-[#071d4f]">Finance Dashboard</h1><p class="mt-1 text-sm text-slate-500">Paid sales by stock owner. Warehouse and supplier-direct sales stay separate.</p></div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8"><div class="mx-auto max-w-[1500px] space-y-5">
        <x-dashboard-guide
            eyebrow="Finance workflow"
            title="How to read this report"
            intro="This workspace is read-only. Use the filters and settlement columns to reconcile paid sales without mixing EduKit warehouse stock with supplier-direct stock."
            :steps="[
                ['title' => 'Set the reporting period', 'description' => 'Choose a source and date range, or leave the filters broad to review all paid sales.', 'route' => 'finance.dashboard', 'action' => 'Refresh report'],
                ['title' => 'Separate stock owners', 'description' => 'EduKit warehouse sales show stock cost and gross profit; supplier-direct sales show supplier payable and EduKit margin.'],
                ['title' => 'Check settlement', 'description' => 'Supplier-direct earnings become earned after delivery; use the settlement column to identify pending or reserved amounts.'],
                ['title' => 'Reconcile each line', 'description' => 'Use the order reference, product, quantity, revenue and cost/payable columns to trace a paid sale back to its source.'],
            ]"
            :notes="[
                'Finance does not approve products, stock, users or deliveries from this workspace.',
                'Operational corrections belong in the administrator workspace and should be followed by a report refresh.',
            ]"
        />
        <form method="GET" action="{{ route('finance.dashboard') }}" class="grid gap-3 rounded-md border border-slate-200 bg-white p-4 sm:grid-cols-4">
            <select name="source" class="rounded-md border-slate-300 text-sm"><option value="">All stock owners</option><option value="edukit" @selected($source === 'edukit')>EduKit warehouse</option><option value="supplier" @selected($source === 'supplier')>Supplier direct</option></select>
            <input type="date" name="from" value="{{ $from?->format('Y-m-d') }}" class="rounded-md border-slate-300 text-sm" aria-label="From date">
            <input type="date" name="to" value="{{ $to?->format('Y-m-d') }}" class="rounded-md border-slate-300 text-sm" aria-label="To date">
            <button class="rounded-md bg-[#07215f] px-4 py-2 text-sm font-bold text-white">Filter</button>
        </form>
        <section class="grid gap-4 md:grid-cols-2">
            <article class="rounded-md border border-blue-200 bg-white p-5"><p class="text-xs font-bold uppercase text-blue-700">EduKit warehouse</p><div class="mt-4 grid grid-cols-2 gap-4 text-sm"><div><p class="text-slate-500">Revenue</p><p class="text-xl font-black text-[#071d4f]">UGX {{ number_format($warehouse?->revenue ?? 0) }}</p></div><div><p class="text-slate-500">Gross profit</p><p class="text-xl font-black text-blue-700">UGX {{ number_format($warehouse?->profit ?? 0) }}</p></div><div><p class="text-slate-500">Stock cost</p><p class="font-bold">UGX {{ number_format($warehouse?->cost ?? 0) }}</p></div><div><p class="text-slate-500">Units sold</p><p class="font-bold">{{ number_format($warehouse?->units ?? 0) }}</p></div></div></article>
            <article class="rounded-md border border-orange-200 bg-white p-5"><p class="text-xs font-bold uppercase text-orange-700">Supplier direct</p><div class="mt-4 grid grid-cols-2 gap-4 text-sm"><div><p class="text-slate-500">Revenue</p><p class="text-xl font-black text-[#071d4f]">UGX {{ number_format($supplier?->revenue ?? 0) }}</p></div><div><p class="text-slate-500">EduKit margin</p><p class="text-xl font-black text-orange-700">UGX {{ number_format($supplier?->profit ?? 0) }}</p></div><div><p class="text-slate-500">Supplier payable</p><p class="font-bold">UGX {{ number_format($supplier?->supplier_payable ?? 0) }}</p></div><div><p class="text-slate-500">Earned after delivery</p><p class="font-bold">UGX {{ number_format($supplierSettlement['earned'] ?? 0) }}</p></div></div></article>
        </section>
        <section class="overflow-hidden rounded-md border border-slate-200 bg-white"><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Product</th><th class="px-4 py-3">Owner/source</th><th class="px-4 py-3">Revenue</th><th class="px-4 py-3">Cost/payable</th><th class="px-4 py-3">EduKit profit</th><th class="px-4 py-3">Settlement</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($items as $item)<tr><td class="px-4 py-3 font-semibold">{{ $item->shoppingList?->reference }}</td><td class="px-4 py-3">{{ $item->product_name }}<span class="block text-xs text-slate-500">{{ number_format($item->quantity) }} units</span></td><td class="px-4 py-3"><span class="font-semibold {{ $item->fulfilment_source === 'supplier' ? 'text-orange-700' : 'text-blue-700' }}">{{ $item->fulfilment_source === 'supplier' ? ($item->supplier?->business_name ?? 'Supplier') : 'EduKit warehouse' }}</span></td><td class="px-4 py-3">UGX {{ number_format($item->line_total) }}</td><td class="px-4 py-3">UGX {{ number_format($item->cost_total) }}</td><td class="px-4 py-3 font-bold">UGX {{ number_format($item->profit_total) }}</td><td class="px-4 py-3">{{ $item->fulfilment_source === 'supplier' ? ucfirst($item->settlement_status) : 'Owned' }}</td></tr>@empty<tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">No paid sales match these filters.</td></tr>@endforelse
        </tbody></table></div></section>
        {{ $items->links() }}
    </div></div>
</x-portal-layout>
