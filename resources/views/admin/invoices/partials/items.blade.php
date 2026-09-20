@php
    $lineItems = $invoice->relationLoaded('lineItems') ? $invoice->lineItems : collect();
    $legacyItems = collect($invoice->cart_items ?? []);
@endphp

@if ($lineItems->isNotEmpty() || $legacyItems->isNotEmpty())
    <div class="mt-6 border-t border-gray-100 pt-5">
        <p class="text-sm font-semibold text-gray-500">Cart items</p>
        <div class="mt-3 divide-y divide-gray-100 rounded border border-gray-200">
            @foreach ($lineItems->isNotEmpty() ? $lineItems : $legacyItems as $item)
                <div class="grid gap-2 p-3 text-sm sm:grid-cols-[1fr_auto]">
                    <div>
                        <p class="font-bold text-gray-950">{{ data_get($item, 'product_name') ?? data_get($item, 'name') }}</p>
                        <p class="text-xs text-gray-500">{{ data_get($item, 'sku') }} | Qty {{ data_get($item, 'quantity') }} | UGX {{ number_format(data_get($item, 'unit_price')) }} each</p>
                        <p class="mt-1 text-xs font-semibold {{ data_get($item, 'fulfilment_source', 'edukit') === 'supplier' ? 'text-emerald-700' : 'text-blue-700' }}">{{ data_get($item, 'fulfilment_source', 'edukit') === 'supplier' ? 'Supplier direct fulfilment' : 'EduKit warehouse fulfilment' }}</p>
                        @if(data_get($item, 'profit_total') !== null)<p class="mt-1 text-xs text-gray-500">Cost: UGX {{ number_format(data_get($item, 'cost_total')) }} | EduKit profit: UGX {{ number_format(data_get($item, 'profit_total')) }}@if(data_get($item, 'supplier_payable') > 0) | Supplier payable: UGX {{ number_format(data_get($item, 'supplier_payable')) }}@endif</p>@endif
                    </div>
                    <p class="font-bold text-gray-950">UGX {{ number_format(data_get($item, 'line_total')) }}</p>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="mt-6 rounded border border-dashed border-gray-300 bg-gray-50 p-5 text-sm text-gray-600">
        This invoice came from an uploaded shopping list. Download and review the submitted file from the intake screen.
    </div>
@endif
