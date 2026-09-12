@if ($invoice->cart_items)
    <div class="mt-6 border-t border-gray-100 pt-5">
        <p class="text-sm font-semibold text-gray-500">Cart items</p>
        <div class="mt-3 divide-y divide-gray-100 rounded border border-gray-200">
            @foreach ($invoice->cart_items as $item)
                <div class="grid gap-2 p-3 text-sm sm:grid-cols-[1fr_auto]">
                    <div>
                        <p class="font-bold text-gray-950">{{ $item['name'] }}</p>
                        <p class="text-xs text-gray-500">{{ $item['sku'] }} | Qty {{ $item['quantity'] }} | UGX {{ number_format($item['unit_price']) }} each</p>
                    </div>
                    <p class="font-bold text-gray-950">UGX {{ number_format($item['line_total']) }}</p>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="mt-6 rounded border border-dashed border-gray-300 bg-gray-50 p-5 text-sm text-gray-600">
        This invoice came from an uploaded shopping list. Download and review the submitted file from the intake screen.
    </div>
@endif
