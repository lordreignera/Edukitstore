<section class="rounded-md border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="text-sm font-extrabold text-slate-950">Recent Inventory Movements</h2>
        <p class="mt-1 text-xs text-slate-500">Latest stock and sales records for audit review.</p>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($recentMovements as $movement)
            <div class="grid gap-2 px-5 py-3 text-sm md:grid-cols-[1fr_160px_140px_140px]">
                <div>
                    <p class="font-bold text-slate-950">{{ $movement->product_name ?? $movement->product?->name ?? 'Deleted product' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $movementLabels[$movement->type] ?? str($movement->type)->headline() }} | {{ $movement->sku ?? 'No code' }}</p>
                </div>
                <p class="font-semibold text-slate-700">{{ number_format($movement->quantity) }} units</p>
                <p class="font-semibold text-slate-700">UGX {{ number_format($movement->profit) }} profit</p>
                <p class="text-xs font-semibold text-slate-500">{{ $movement->created_at->format('d M Y H:i') }}</p>
            </div>
        @empty
            <p class="px-5 py-8 text-center text-sm text-slate-500">Inventory movements will appear after stock intake, transfers or paid sales.</p>
        @endforelse
    </div>
</section>
