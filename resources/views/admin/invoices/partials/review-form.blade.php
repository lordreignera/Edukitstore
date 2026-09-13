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
            <p class="font-bold">School fee was calculated at checkout.</p>
            <p class="mt-1 text-xs leading-5 text-emerald-800">To change future fees, edit the school record. This invoice keeps the fee that was shown to the customer.</p>
        </div>
    @else
        <div>
            <label class="text-sm font-medium text-gray-700" for="estimated_total">Estimated total (UGX)</label>
            <input id="estimated_total" name="estimated_total" type="number" min="0" step="1" value="{{ old('estimated_total', $invoice->estimated_total) }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            @error('estimated_total') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-500">For uploaded shopping lists, enter the full invoice total after matching items manually.</p>
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700" for="delivery_fee">Delivery/convenience fee (UGX)</label>
            <input id="delivery_fee" name="delivery_fee" type="number" min="0" step="1" value="{{ old('delivery_fee', $invoice->delivery_fee) }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            @error('delivery_fee') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-500">Use the school fee as guidance when preparing manual uploaded-list invoices.</p>
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
