@php
    $lineItems = $invoice->relationLoaded('lineItems') ? $invoice->lineItems : collect();
    $legacyItems = collect($invoice->cart_items ?? []);
@endphp

@if ($lineItems->isNotEmpty() || $legacyItems->isNotEmpty())
    <div class="mt-6 border-t border-gray-100 pt-5">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-orange-700">Items to fulfil</p>
                <h3 class="mt-1 text-base font-extrabold text-slate-950">Order products</h3>
            </div>
            <p class="text-xs font-semibold text-slate-500">{{ $lineItems->isNotEmpty() ? $lineItems->count() : $legacyItems->count() }} line items</p>
        </div>
        <div class="mt-3 divide-y divide-gray-100 rounded border border-gray-200">
            @foreach ($lineItems->isNotEmpty() ? $lineItems : $legacyItems as $item)
                @php
                    $itemProduct = data_get($item, 'product');
                    $itemImage = $itemProduct?->image_url ?? data_get($item, 'image_url');
                    $itemName = data_get($item, 'product_name') ?? data_get($item, 'name');
                    $itemSource = data_get($item, 'fulfilment_source', 'edukit');
                @endphp
                <article class="grid grid-cols-[64px_minmax(0,1fr)] gap-3 p-3 text-sm sm:grid-cols-[80px_minmax(0,1fr)_auto] sm:items-center">
                    <div class="size-16 overflow-hidden rounded-md border border-slate-200 bg-slate-50 sm:size-20">
                        <x-ui.responsive-image :src="$itemImage" :alt="$itemName" label="Product image unavailable" image-class="h-full w-full object-contain p-1" />
                    </div>
                    <div class="min-w-0">
                        <p class="font-extrabold leading-5 text-slate-950">{{ $itemName }}</p>
                        <p class="mt-1 text-xs text-slate-600">{{ data_get($item, 'sku') ?: 'No SKU' }} <span aria-hidden="true">&middot;</span> Qty <strong>{{ number_format((int) data_get($item, 'quantity')) }}</strong> <span aria-hidden="true">&middot;</span> UGX {{ number_format((float) data_get($item, 'unit_price')) }} each</p>
                        <p class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $itemSource === 'supplier' ? 'bg-orange-50 text-orange-800' : 'bg-blue-50 text-blue-800' }}">{{ $itemSource === 'supplier' ? 'Supplier direct fulfilment' : 'EduKit warehouse fulfilment' }}</p>
                        @if (data_get($item, 'profit_total') !== null)
                            <p class="mt-1 text-[11px] text-slate-500">Cost: UGX {{ number_format((float) data_get($item, 'cost_total')) }} <span aria-hidden="true">&middot;</span> EduKit profit: UGX {{ number_format((float) data_get($item, 'profit_total')) }}@if ((float) data_get($item, 'supplier_payable') > 0) <span aria-hidden="true">&middot;</span> Supplier payable: UGX {{ number_format((float) data_get($item, 'supplier_payable')) }}@endif</p>
                        @endif
                    </div>
                    <p class="col-start-2 font-black text-slate-950 sm:col-start-auto sm:text-right">UGX {{ number_format((float) data_get($item, 'line_total')) }}</p>
                </article>
            @endforeach
        </div>
    </div>
@else
    <div class="mt-6 rounded border border-dashed border-gray-300 bg-gray-50 p-5 text-sm text-gray-600">
        This invoice came from an uploaded shopping list. Download and review the submitted file from the intake screen.
    </div>
@endif
