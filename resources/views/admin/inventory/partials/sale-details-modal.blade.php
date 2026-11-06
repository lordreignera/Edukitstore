@php($saleProduct = $movement->product)

<x-admin-modal
    name="sale-details-{{ $movement->id }}"
    title="Sale details"
    description="Recorded sale for {{ $movement->product_name ?? $saleProduct?->name ?? 'Deleted product' }}."
    maxWidth="max-w-2xl"
>
    <div class="space-y-5">
        <section class="flex items-center gap-4 rounded-lg border border-orange-100 bg-orange-50/60 p-4">
            <div class="size-20 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-white p-1">
                @if ($saleProduct?->image_url)
                    <x-ui.responsive-image :src="$saleProduct->image_url" :alt="$saleProduct->name" :label="$saleProduct->category?->name ?? 'Product'" image-class="h-full w-full object-contain" />
                @else
                    <div class="grid h-full place-items-center text-xs font-bold text-slate-400">No image</div>
                @endif
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wide text-orange-700">{{ $saleProduct?->category?->name ?? 'Product sale' }}</p>
                <h3 class="mt-1 text-lg font-extrabold leading-tight text-[#071d4f]">{{ $movement->product_name ?? $saleProduct?->name ?? 'Deleted product' }}</h3>
                <p class="mt-1 text-sm text-slate-600">SKU: {{ $movement->sku ?? $saleProduct?->sku ?? 'Not recorded' }}</p>
            </div>
        </section>

        <section aria-label="Recorded sale totals">
            <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Sale totals</h3>
            <dl class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <div class="rounded-md border border-slate-200 p-3"><dt class="text-[11px] font-semibold text-slate-500">Units</dt><dd class="mt-1 text-lg font-extrabold text-slate-950">{{ number_format($movement->quantity) }}</dd></div>
                <div class="rounded-md border border-slate-200 p-3"><dt class="text-[11px] font-semibold text-slate-500">Revenue</dt><dd class="mt-1 text-sm font-extrabold text-slate-950">UGX {{ number_format($movement->total_revenue) }}</dd></div>
                <div class="rounded-md border border-slate-200 p-3"><dt class="text-[11px] font-semibold text-slate-500">Stock cost</dt><dd class="mt-1 text-sm font-extrabold text-slate-950">UGX {{ number_format($movement->total_cost) }}</dd></div>
                <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3"><dt class="text-[11px] font-semibold text-emerald-800">Gross profit</dt><dd class="mt-1 text-sm font-extrabold text-emerald-900">UGX {{ number_format($movement->profit) }}</dd></div>
            </dl>
        </section>

        <section class="grid gap-4 rounded-md border border-slate-200 p-4 sm:grid-cols-2">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Sale record</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Sold</dt><dd class="text-right font-semibold text-slate-800">{{ $movement->occurred_at?->format('d M Y, H:i') ?? 'Not recorded' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Order</dt><dd class="text-right font-semibold text-slate-800">
                        @if ($movement->shoppingList && auth()->user()->can('invoices.view'))
                            <a href="{{ route('admin.invoices.show', $movement->shoppingList) }}" class="text-orange-700 hover:underline">{{ $movement->shoppingList->reference }}</a>
                        @else
                            {{ $movement->shoppingList?->reference ?? $movement->reference ?? 'Not linked' }}
                        @endif
                    </dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Unit selling price</dt><dd class="text-right font-semibold text-slate-800">UGX {{ number_format($movement->unit_price) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Unit stock cost</dt><dd class="text-right font-semibold text-slate-800">UGX {{ number_format($movement->unit_cost) }}</dd></div>
                    @if ($movement->shoppingList?->payment_provider === 'demo')
                        <div class="pt-1"><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Demo/test payment</span></div>
                    @endif
                </dl>
            </div>
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Current catalogue details</h3>
                @if ($saleProduct)
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Brand</dt><dd class="text-right font-semibold text-slate-800">{{ $saleProduct->brand ?: 'Not set' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Colour</dt><dd class="text-right font-semibold text-slate-800">{{ $saleProduct->color ?: 'Not set' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Unit</dt><dd class="text-right font-semibold text-slate-800">{{ $saleProduct->unit ?: 'Not set' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Current display stock</dt><dd class="text-right font-semibold text-slate-800">{{ number_format($saleProduct->stock_quantity) }}</dd></div>
                        @can('products.view')
                            <div class="pt-1"><a href="{{ route('admin.products.show', $saleProduct) }}" class="font-bold text-orange-700 hover:underline">Open full product record</a></div>
                        @endcan
                    </dl>
                @else
                    <p class="mt-3 text-sm text-slate-600">The product has since been removed; the sale snapshot above is retained.</p>
                @endif
            </div>
        </section>

        @if ($saleProduct?->description)
            <p class="text-sm leading-6 text-slate-600">{{ $saleProduct->description }}</p>
        @endif
    </div>
</x-admin-modal>
