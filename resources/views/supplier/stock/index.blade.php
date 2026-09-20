@php
    $navigation = config('portals.supplier_navigation');
@endphp
<x-portal-layout title="Supplied Stock" portal-name="Supplier Portal" :navigation="$navigation" search-action="{{ route('supplier.stock.index') }}" search-placeholder="Search supplied products...">
    <x-slot name="header"><div><h1 class="text-2xl font-extrabold text-[#071d4f]">Supplied stock</h1><p class="mt-1 text-sm text-slate-500">Stock batches received from {{ $supplier->business_name }}.</p></div></x-slot>
    <div class="px-4 py-6 sm:px-6 lg:px-8"><div class="mx-auto max-w-[1500px]">
        <form method="GET" class="mb-5 flex gap-2 sm:hidden"><input name="q" value="{{ $search }}" placeholder="Search products..." class="min-w-0 flex-1 rounded-md border-slate-300"><button class="rounded-md bg-[#071d4f] px-4 font-bold text-white">Search</button></form>
        <section class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Batch</th><th class="px-5 py-3">Received</th><th class="px-5 py-3">Unit cost</th><th class="px-5 py-3">Supplied</th><th class="px-5 py-3">Remaining</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse ($batches as $batch)<tr><td class="px-5 py-4"><p class="font-bold">{{ $batch->product->name }}</p><p class="text-xs text-slate-500">{{ $batch->product->sku }}</p></td><td class="px-5 py-4 text-slate-600">{{ $batch->batch_reference }}</td><td class="px-5 py-4 text-slate-600">{{ $batch->received_at->format('d M Y') }}</td><td class="px-5 py-4 font-semibold">UGX {{ number_format($batch->unit_cost) }}</td><td class="px-5 py-4">{{ number_format($batch->quantity_received) }}</td><td class="px-5 py-4"><span class="rounded bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">{{ number_format($batch->remaining_quantity) }}</span></td></tr>@empty<tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No matching stock records.</td></tr>@endforelse
        </tbody></table></div></section><div class="mt-5">{{ $batches->links() }}</div>
    </div></div>
</x-portal-layout>
