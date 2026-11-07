@php
    $lines = $invoice->lineItems;
    $canPack = $invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID
        && ($invoice->status === \App\Models\ShoppingList::STATUS_QUOTED || ($invoice->payment_exception_type === 'stock' && $invoice->status === \App\Models\ShoppingList::STATUS_REVIEWING))
        && ! $invoice->driver_started_at && ! $invoice->driver_reached_at
        && ! $invoice->customer_received_at && ! $invoice->delivery_confirmed_at
        && ! $invoice->manual_refunded_at
        && (! $invoice->payment_exception || $invoice->payment_exception_type === 'stock')
        && $lines->isNotEmpty();
    $packingSaved = (bool) $invoice->packed_at;
    $packedUnits = (int) $lines->sum('quantity');
    $allItemsUnavailable = $packingSaved && $packedUnits === 0;
    $availabilityChecked = $packingSaved;
    $itemsPacked = $packingSaved && $packedUnits > 0;
    $driverAssigned = (bool) $invoice->assigned_driver_id;
@endphp

@if ($lines->isNotEmpty())
    <section class="mx-auto mb-6 max-w-7xl rounded-lg border border-orange-200 bg-white shadow-sm" aria-labelledby="fulfilment-heading">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-orange-100 bg-orange-50/60 px-5 py-4 sm:px-6">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wide text-orange-800">Order fulfilment</p>
                <h2 id="fulfilment-heading" class="mt-1 text-xl font-black text-[#07215f]">{{ $invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? 'Check, pack and assign this order' : 'Products on this order' }}</h2>
                <p class="mt-1 max-w-3xl text-sm text-slate-600">
                    @if ($invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID)
                        Mark each item available or unavailable. Confirm available items are packed, then choose the driver — all from this screen.
                    @else
                        Verify these products and quantities before releasing the invoice for payment.
                    @endif
                </p>
            </div>
            <div class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm">
                <span class="font-bold text-slate-500">Payment</span>
                <span class="ml-2 font-black text-slate-900">{{ \App\Models\ShoppingList::paymentStatuses()[$invoice->payment_status] ?? ucfirst($invoice->payment_status) }}</span>
            </div>
        </div>

        @if ($invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID)
            <ol class="grid gap-2 border-b border-slate-100 p-4 text-sm sm:grid-cols-3 sm:p-5" aria-label="Fulfilment progress">
                <li class="rounded-md border p-3 {{ $availabilityChecked ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : 'border-orange-200 bg-orange-50 text-orange-950' }}" @if (! $availabilityChecked) aria-current="step" @endif>
                    <span class="flex items-center gap-2 font-extrabold"><span class="grid size-7 shrink-0 place-items-center rounded-full {{ $availabilityChecked ? 'bg-emerald-700 text-white' : 'bg-orange-700 text-white' }}">{{ $availabilityChecked ? '✓' : '1' }}</span>Availability {{ $availabilityChecked ? 'checked' : 'to check' }}</span>
                    <span class="mt-1 block pl-9 text-xs">{{ $availabilityChecked ? 'Saved '.$invoice->packed_at->format('d M, H:i') : 'Review every product below.' }}</span>
                </li>
                <li class="rounded-md border p-3 {{ $itemsPacked ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : ($allItemsUnavailable ? 'border-slate-200 bg-slate-50 text-slate-600' : 'border-slate-200 bg-white text-slate-700') }}" @if (! $itemsPacked && ! $allItemsUnavailable) aria-current="step" @endif>
                    <span class="flex items-center gap-2 font-extrabold"><span class="grid size-7 shrink-0 place-items-center rounded-full {{ $itemsPacked ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $itemsPacked ? '✓' : '2' }}</span>{{ $allItemsUnavailable ? 'Packing not needed' : ($itemsPacked ? 'Items packed' : 'Pack available items') }}</span>
                    <span class="mt-1 block pl-9 text-xs">{{ $itemsPacked ? $packedUnits.' unit(s) packed' : ($allItemsUnavailable ? 'No available items to pack.' : 'Waiting for availability check.') }}</span>
                </li>
                <li class="rounded-md border p-3 {{ $driverAssigned ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : ($allItemsUnavailable ? 'border-slate-200 bg-slate-50 text-slate-600' : 'border-slate-200 bg-white text-slate-700') }}" @if (! $driverAssigned && ! $allItemsUnavailable) aria-current="step" @endif>
                    <span class="flex items-center gap-2 font-extrabold"><span class="grid size-7 shrink-0 place-items-center rounded-full {{ $driverAssigned ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $driverAssigned ? '✓' : '3' }}</span>{{ $driverAssigned ? 'Driver assigned' : ($allItemsUnavailable ? 'No driver needed' : 'Assign a driver') }}</span>
                    <span class="mt-1 block pl-9 text-xs">{{ $driverAssigned ? $invoice->assignedDriver?->name : ($allItemsUnavailable ? 'Record the refund to close this order.' : 'Waiting for packing to be saved.') }}</span>
                </li>
            </ol>
            @if ($packingSaved && ! $allItemsUnavailable)
                <p class="mx-4 mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 sm:mx-5">Fulfilment is saved: {{ $packedUnits }} unit(s) packed{{ $invoice->assignedDriver ? ' and assigned to '.$invoice->assignedDriver->name : '' }}. You can update it until the driver starts the delivery.</p>
            @elseif ($allItemsUnavailable)
                <p class="mx-4 mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-950 sm:mx-5">Availability check is saved. No items can be delivered; record the manual refund below to close the order.</p>
            @endif
        @endif

        @if ($canPack)
            <form method="POST" action="{{ route('admin.invoices.fulfilment', $invoice) }}" class="p-4 sm:p-6" data-packing-form>
                @csrf
                @method('PATCH')
                <div class="space-y-3">
                    @foreach ($lines as $item)
                        @php
                            $orderedQuantity = (int) ($item->ordered_quantity ?: ($item->quantity + $item->unavailable_quantity));
                            $unavailableValue = old("items.{$item->id}.unavailable_quantity", $item->unavailable_quantity);
                            $itemImage = $item->product?->image_url;
                            $availability = (int) $unavailableValue === 0 ? 'available' : ((int) $unavailableValue >= $orderedQuantity ? 'unavailable' : 'partial');
                        @endphp
                        <article class="grid gap-3 rounded-md border border-slate-200 p-3 sm:p-4 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.85fr)] lg:items-center" data-product-row data-ordered-quantity="{{ $orderedQuantity }}">
                            <div class="flex gap-3">
                                @if ($itemImage)
                                    <button type="button" class="size-20 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-50 p-1 hover:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500" data-image-preview data-image-url="{{ $itemImage }}" data-image-name="{{ $item->product_name }}" aria-label="View full image of {{ $item->product_name }}"><x-ui.responsive-image :src="$itemImage" :alt="$item->product_name" label="Product image unavailable" image-class="h-full w-full object-contain" /></button>
                                @else
                                    <div class="size-20 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-50 p-1"><x-ui.responsive-image :src="$itemImage" :alt="$item->product_name" label="Product image unavailable" image-class="h-full w-full object-contain" /></div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-extrabold leading-5 text-slate-950">{{ $item->product_name }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->sku ?: 'No SKU' }} · {{ $item->fulfilment_source === 'supplier' ? 'Supplier' : 'EduKit stock' }}</p>
                                    <p class="mt-1 text-sm font-bold text-[#07215f]">{{ $orderedQuantity }} ordered · UGX {{ number_format((float) $item->unit_price) }} each</p>
                                    <p class="mt-1 text-xs font-bold text-emerald-800" data-pack-quantity>Ready to pack: {{ $orderedQuantity - (int) $unavailableValue }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700" for="availability_{{ $item->id }}">Availability</label>
                                <select id="availability_{{ $item->id }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600" data-availability-select>
                                    <option value="available" @selected($availability === 'available')>Available</option>
                                    @if ($orderedQuantity > 1)<option value="partial" @selected($availability === 'partial')>Partially unavailable</option>@endif
                                    <option value="unavailable" @selected($availability === 'unavailable')>Unavailable</option>
                                </select>
                            </div>
                            <div class="mt-3 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] gap-2 border-t border-red-100 pt-3 sm:col-span-2" data-shortage-fields style="{{ (int) $unavailableValue === 0 ? 'display: none;' : '' }}">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700" for="unavailable_{{ $item->id }}">Unavailable qty</label>
                                    <input id="unavailable_{{ $item->id }}" name="items[{{ $item->id }}][unavailable_quantity]" type="number" min="0" max="{{ $orderedQuantity }}" value="{{ $unavailableValue }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600" data-unavailable-quantity>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-red-800" for="reason_{{ $item->id }}">Why is it unavailable?</label>
                                    <select id="reason_{{ $item->id }}" name="items[{{ $item->id }}][unavailable_reason]" class="mt-1 w-full rounded border-red-200 text-sm focus:border-orange-600 focus:ring-orange-600" data-unavailable-reason>
                                        <option value="">Choose a reason</option>
                                        @foreach (\App\Models\ShoppingListItem::unavailableReasons() as $reason => $reasonLabel)
                                            <option value="{{ $reason }}" @selected(old("items.{$item->id}.unavailable_reason", $item->unavailable_reason) === $reason)>{{ $reasonLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-bold text-slate-700" for="note_{{ $item->id }}">Note for customer <span class="font-normal text-slate-500">(optional)</span></label>
                                    <input id="note_{{ $item->id }}" name="items[{{ $item->id }}][unavailable_note]" maxlength="500" value="{{ old("items.{$item->id}.unavailable_note", $item->unavailable_note) }}" placeholder="Short explanation" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                                </div>
                            </div>
                            @error("items.{$item->id}.unavailable_reason") <p class="mt-2 text-xs font-bold text-red-700">{{ $message }}</p> @enderror
                        </article>
                    @endforeach
                </div>

                <div class="mt-4 grid gap-4 border-t border-slate-100 pt-4 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3" data-pack-confirmation-wrap>
                        <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Step 2 · Pack</p>
                        <label class="mt-2 flex cursor-pointer items-start gap-2 text-sm font-semibold text-slate-800"><input type="checkbox" name="packed_items_confirmed" value="1" @checked(old('packed_items_confirmed', $invoice->packed_at)) class="mt-0.5 rounded border-slate-300 text-orange-600 focus:ring-orange-500" data-pack-confirmation><span>I confirm the available quantities above are packed and ready.</span></label>
                        @error('packed_items_confirmed') <p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-800" for="fulfilment_driver">Step 3 · Assign driver</label>
                        <select id="fulfilment_driver" name="assigned_driver_id" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600 sm:max-w-xl" data-driver-select>
                            <option value="">Select approved and available driver</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected((int) old('assigned_driver_id', $invoice->assigned_driver_id) === $driver->id)>{{ $driver->name }}{{ $driver->phone ? ' · '.$driver->phone : '' }}</option>
                            @endforeach
                        </select>
                        @error('assigned_driver_id') <p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-slate-500" data-driver-hint>Choose an approved, available driver for packed items.</p>
                    </div>
                    <button class="min-h-11 rounded-md bg-orange-700 px-5 py-3 text-sm font-extrabold text-white hover:bg-orange-800 focus:outline-none focus:ring-2 focus:ring-orange-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70" data-packing-submit data-delivery-label="{{ $invoice->packed_at ? 'Update packing & driver' : 'Confirm packing & assign driver' }}">{{ $invoice->packed_at ? 'Update packing & driver' : 'Confirm packing & assign driver' }}</button>
                </div>
                @error('fulfilment') <p class="mt-3 rounded border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800">{{ $message }}</p> @enderror
            </form>
            <script>
                (() => {
                    const form = document.querySelector('[data-packing-form]');
                    const driver = form?.querySelector('[data-driver-select]');
                    const confirmation = form?.querySelector('[data-pack-confirmation]');
                    if (!form || !driver || !confirmation) return;
                    const updateRow = row => {
                        const status = row.querySelector('[data-availability-select]');
                        const qty = row.querySelector('[data-unavailable-quantity]');
                        const reason = row.querySelector('[data-unavailable-reason]');
                        const max = Number(qty.max || row.dataset.orderedQuantity || 0);
                        if (status.value === 'available') qty.value = '0';
                        if (status.value === 'unavailable') qty.value = String(max);
                        if (status.value === 'partial' && Number(qty.value) <= 0) qty.value = '1';
                        if (status.value === 'partial' && Number(qty.value) >= max) status.value = 'unavailable';
                        const unavailable = Math.min(max, Math.max(0, Number(qty.value || 0)));
                        const isShort = unavailable > 0;
                        row.querySelector('[data-shortage-fields]').style.display = isShort ? '' : 'none';
                        reason.required = isShort;
                        qty.required = isShort;
                        row.querySelector('[data-pack-quantity]').textContent = `Ready to pack: ${max - unavailable}`;
                        return max - unavailable;
                    };
                    const updateForm = () => {
                        const rows = [...form.querySelectorAll('[data-product-row]')];
                        const deliverableCount = rows.reduce((sum, row) => sum + updateRow(row), 0);
                        const hasDeliverableItems = deliverableCount > 0;
                        const submit = form.querySelector('[data-packing-submit]');
                        driver.required = hasDeliverableItems;
                        driver.disabled = !hasDeliverableItems;
                        if (!hasDeliverableItems) driver.value = '';
                        confirmation.required = hasDeliverableItems;
                        confirmation.closest('[data-pack-confirmation-wrap]').hidden = !hasDeliverableItems;
                        submit.textContent = hasDeliverableItems ? submit.dataset.deliveryLabel : 'Save shortages & continue to refund';
                    };
                    form.addEventListener('input', updateForm);
                    form.addEventListener('change', updateForm);
                    form.addEventListener('submit', () => {
                        const submit = form.querySelector('[data-packing-submit]');
                        submit.disabled = true;
                        submit.textContent = 'Saving fulfilment…';
                        submit.setAttribute('aria-busy', 'true');
                    });
                    updateForm();
                })();
            </script>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($lines as $item)
                    <article class="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
                        @if ($item->product?->image_url)
                            <button type="button" class="size-16 shrink-0 overflow-hidden rounded border border-slate-200 bg-slate-50 p-1 hover:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500" data-image-preview data-image-url="{{ $item->product->image_url }}" data-image-name="{{ $item->product_name }}" aria-label="View full image of {{ $item->product_name }}"><x-ui.responsive-image :src="$item->product->image_url" :alt="$item->product_name" label="Product image unavailable" image-class="h-full w-full object-contain" /></button>
                        @else
                            <div class="size-16 shrink-0 overflow-hidden rounded border border-slate-200 bg-slate-50 p-1"><x-ui.responsive-image :src="null" :alt="$item->product_name" label="Product image unavailable" image-class="h-full w-full object-contain" /></div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-slate-950">{{ $item->product_name }}</p>
                            <p class="text-xs text-slate-500">{{ $item->sku ?: 'No SKU' }} · {{ $item->fulfilment_source === 'supplier' ? 'Supplier' : 'EduKit stock' }}</p>
                        </div>
                        <p class="text-sm font-bold text-slate-800">{{ (int) $item->quantity }} / {{ (int) ($item->ordered_quantity ?: $item->quantity) }} to deliver</p>
                        @if ((int) $item->unavailable_quantity > 0)
                            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700">{{ $item->unavailable_quantity }} unavailable · {{ \App\Models\ShoppingListItem::unavailableReasons()[$item->unavailable_reason] ?? 'Other reason' }}</span>
                        @endif
                    </article>
                @endforeach
            </div>
            @if ($invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID && $invoice->payment_exception && $invoice->payment_exception_type !== 'stock')
                <p class="m-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Resolve the payment exception before packing or assigning this order.</p>
            @endif
        @endif

        @if (in_array($invoice->payment_status, [\App\Models\ShoppingList::PAYMENT_PAID, \App\Models\ShoppingList::PAYMENT_REFUNDED], true) && $invoice->manualRefundDue() > 0)
            <div class="border-t border-slate-100 bg-slate-50 px-4 py-4 sm:px-6">
                @if ($invoice->manual_refunded_at)
                    <div class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-900">
                        <p class="font-extrabold">{{ $invoice->payment_provider === 'demo' ? 'Dry-run refund recorded' : 'Manual refund recorded' }} · UGX {{ number_format($invoice->manual_refund_amount) }}</p>
                        <p class="mt-1">Reference: {{ $invoice->manual_refund_reference }} · {{ $invoice->manual_refunded_at->format('d M Y, H:i') }}{{ $invoice->manualRefundRecordedBy ? ' · Recorded by '.$invoice->manualRefundRecordedBy->name : '' }}</p>
                        @if ($invoice->payment_provider === 'demo')<p class="mt-1 font-semibold">No money was collected or returned in demo mode.</p>@endif
                    </div>
                @elseif ($invoice->packed_at)
                    <form method="POST" action="{{ route('admin.invoices.manual-refund', $invoice) }}" class="grid gap-3 rounded-md border border-orange-200 bg-orange-50 p-4 sm:grid-cols-[1fr_auto] sm:items-end">
                        @csrf
                        @method('PATCH')
                        <div>
                            <h3 class="font-extrabold text-orange-950">Record refund made outside EduKit</h3>
                            <p class="mt-1 text-sm text-orange-900">
                                @if ($invoice->payment_provider === 'demo')
                                    Dry-run refund record: <strong>UGX {{ number_format($invoice->manualRefundOutstanding()) }}</strong>. No money was collected or returned.
                                @else
                                    Refund due: <strong>UGX {{ number_format($invoice->manualRefundOutstanding()) }}</strong>.
                                @endif
                                Partial deliveries keep the convenience fee; it is included only when no products can be delivered.
                            </p>
                            <label for="manual_refund_reference" class="mt-3 block text-xs font-bold text-orange-950">{{ $invoice->payment_provider === 'demo' ? 'Dry-run refund reference' : 'Mobile Money / external transaction reference' }}</label>
                            <input id="manual_refund_reference" name="manual_refund_reference" required maxlength="160" value="{{ old('manual_refund_reference') }}" class="mt-1 w-full rounded border-orange-300 text-sm focus:border-orange-600 focus:ring-orange-600 sm:max-w-xl">
                            @error('manual_refund_reference') <p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p> @enderror
                            @error('refund') <p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <button class="rounded-md bg-[#07215f] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2b72]">{{ $invoice->payment_provider === 'demo' ? 'Record dry-run refund' : 'Confirm refund sent' }}</button>
                    </form>
                @else
                    <p class="rounded-md border border-orange-200 bg-orange-50 p-3 text-sm text-orange-950">Once the packing quantities are saved, record the {{ $invoice->payment_provider === 'demo' ? 'dry-run refund' : 'manual refund' }} here. Current amount: <strong>UGX {{ number_format($invoice->manualRefundOutstanding()) }}</strong>.</p>
                @endif
            </div>
        @endif
        <dialog class="w-[min(96vw,70rem)] max-w-none rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/80" data-product-image-dialog aria-labelledby="product-image-caption">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <p id="product-image-caption" class="font-bold text-slate-900" data-product-image-caption>Product image</p>
                <button type="button" class="rounded border border-slate-300 px-3 py-1.5 text-sm font-bold text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-orange-500" data-close-product-image>Close</button>
            </div>
            <div class="grid min-h-64 max-h-[80vh] place-items-center bg-slate-50 p-4 sm:p-8"><img src="" alt="" class="max-h-[72vh] max-w-full object-contain" data-product-image-large></div>
        </dialog>
        <script>
            (() => {
                const dialog = document.querySelector('[data-product-image-dialog]');
                if (!dialog || typeof dialog.showModal !== 'function') return;
                const image = dialog.querySelector('[data-product-image-large]');
                const caption = dialog.querySelector('[data-product-image-caption]');
                document.querySelectorAll('[data-image-preview]').forEach(button => button.addEventListener('click', () => {
                    image.src = button.dataset.imageUrl;
                    image.alt = button.dataset.imageName || 'Product image';
                    caption.textContent = button.dataset.imageName || 'Product image';
                    dialog.showModal();
                }));
                dialog.querySelector('[data-close-product-image]').addEventListener('click', () => dialog.close());
                dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
            })();
        </script>
    </section>
@endif
