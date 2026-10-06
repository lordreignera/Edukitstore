<form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="mt-5 space-y-5">
    @csrf
    @method('PATCH')

    <div>
        <label class="text-sm font-medium text-gray-700" for="status">Invoice status</label>
        <select id="status" name="status" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $invoice->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    @if ($invoice->source === \App\Models\ShoppingList::SOURCE_CART)
        @include('admin.invoices.partials.summary', ['invoice' => $invoice])

        <div class="rounded border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-950">
            <p class="font-bold">Convenience fee was calculated at checkout.</p>
            <p class="mt-1 text-xs leading-5 text-emerald-800">To change future fees, edit the school record. This invoice keeps the fee that was shown to the customer.</p>
        </div>
    @else
        @if ($invoice->status !== \App\Models\ShoppingList::STATUS_QUOTED && $invoice->payment_status === \App\Models\ShoppingList::PAYMENT_UNPAID)
            <input type="hidden" name="items_present" value="1">
            <div class="space-y-3" data-invoice-items>
                <div class="flex items-center justify-between"><p class="text-sm font-bold text-gray-800">Priced catalogue items</p><button type="button" data-add-invoice-item class="text-sm font-bold text-emerald-700">Add item</button></div>
                @php($rows = old('items', $invoice->lineItems->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_price' => (int) $item->unit_price])->all()))
                <div data-invoice-item-rows class="space-y-2">
                    @foreach ($rows ?: [['product_id' => '', 'quantity' => 1, 'unit_price' => '']] as $index => $row)
                        <div class="grid gap-2 sm:grid-cols-[1fr_100px_140px_auto]" data-invoice-item-row>
                            <select name="items[{{ $index }}][product_id]" aria-label="Product" class="rounded border-gray-300 text-sm"><option value="">Choose product</option>@foreach ($products as $product)<option value="{{ $product->id }}" data-price="{{ (int) $product->price }}" @selected((int) ($row['product_id'] ?? 0) === $product->id)>{{ $product->name }} ({{ $product->sku }})</option>@endforeach</select>
                            <input name="items[{{ $index }}][quantity]" type="number" min="1" value="{{ $row['quantity'] ?? 1 }}" aria-label="Quantity" class="rounded border-gray-300 text-sm">
                            <input name="items[{{ $index }}][unit_price]" type="number" min="1" value="{{ $row['unit_price'] ?? '' }}" aria-label="Unit price in UGX" placeholder="Unit price" class="rounded border-gray-300 text-sm">
                            <button type="button" data-remove-invoice-item class="rounded border border-red-200 px-3 text-xs font-bold text-red-700">Remove</button>
                        </div>
                    @endforeach
                </div>
                @error('items') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-500">The subtotal and total are calculated from these lines. Stock is recorded after verified payment.</p>
            </div>
            <script>
                (() => {
                    const container = document.querySelector('[data-invoice-items]');
                    const rows = container?.querySelector('[data-invoice-item-rows]');
                    if (!rows) return;
                    container.querySelector('[data-add-invoice-item]').addEventListener('click', () => {
                        const row = rows.querySelector('[data-invoice-item-row]').cloneNode(true);
                        const index = Date.now();
                        row.querySelectorAll('[name]').forEach(input => { input.name = input.name.replace(/items\[\d+\]/, `items[${index}]`); input.value = input.type === 'number' && input.name.includes('quantity') ? 1 : ''; });
                        rows.appendChild(row);
                    });
                    rows.addEventListener('click', event => {
                        if (event.target.closest('[data-remove-invoice-item]')) {
                            const row = event.target.closest('[data-invoice-item-row]');
                            if (rows.children.length > 1) row.remove();
                            else row.querySelectorAll('input, select').forEach(input => input.value = '');
                        }
                    });
                    rows.addEventListener('change', event => {
                        if (event.target.matches('select')) {
                            const price = event.target.selectedOptions[0]?.dataset.price;
                            const input = event.target.closest('[data-invoice-item-row]').querySelector('[name$="[unit_price]"]');
                            if (price && !input.value) input.value = price;
                        }
                    });
                })();
            </script>
        @endif

        <div>
            <label class="text-sm font-medium text-gray-700" for="delivery_fee">Convenience fee (UGX)</label>
            <input id="delivery_fee" name="delivery_fee" type="number" min="0" step="1" value="{{ old('delivery_fee', $invoice->delivery_fee) }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            @error('delivery_fee') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-500">The total includes this fee and the item subtotal.</p>
        </div>
    @endif

    <div>
        <label class="text-sm font-medium text-gray-700" for="assigned_driver_id">Assigned driver</label>
        <select id="assigned_driver_id" name="assigned_driver_id" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            <option value="">Select approved driver</option>
            @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}" @selected((int) old('assigned_driver_id', $invoice->assigned_driver_id) === $driver->id)>
                    {{ $driver->name }}{{ $driver->phone ? ' - '.$driver->phone : '' }}{{ $driver->district ? ' - '.$driver->district : '' }}
                </option>
            @endforeach
        </select>
        @error('assigned_driver_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        <p class="mt-1 text-xs text-gray-500">This contact appears on the customer invoice after assignment. Only the assigned driver can complete delivery.</p>
    </div>

    @if ($invoice->assignedDriver)
        <div class="rounded border border-emerald-100 bg-emerald-50 p-4 text-sm">
            <p class="font-bold text-emerald-950">{{ $invoice->assignedDriver->name }}</p>
            <p class="mt-1 text-emerald-800">{{ $invoice->assignedDriver->phone ?? 'No phone recorded' }}</p>
            <p class="mt-1 text-xs text-emerald-700">{{ $invoice->delivery_confirmed_at ? 'Delivered on '.$invoice->delivery_confirmed_at->format('M d, Y H:i') : 'Awaiting driver delivery confirmation.' }}</p>
        </div>
    @endif

    <button class="rounded bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save invoice</button>
</form>
