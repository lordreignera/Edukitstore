@foreach ($movements as $movement)
    @continue(! $movement->isEditable())

    @php($modalName = 'edit-movement-'.$movement->id)

    <x-admin-modal name="{{ $modalName }}" title="Edit inventory movement" description="{{ $movementLabels[$movement->type] ?? str($movement->type)->headline() }} for {{ $movement->product_name ?? $movement->product?->name ?? 'this product' }}.">
        <form method="POST" action="{{ route('admin.inventory.movements.update', $movement) }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_modal" value="{{ $modalName }}">
            <div>
                <label class="text-sm font-bold text-slate-700" for="movement-date-{{ $movement->id }}">Movement date</label>
                <input id="movement-date-{{ $movement->id }}" name="occurred_at" type="date" value="{{ old('_modal') === $modalName ? old('occurred_at') : $movement->occurred_at->toDateString() }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>

            @if ($movement->type === \App\Models\InventoryMovement::TYPE_OPENING_STOCK)
                <div>
                    <label class="text-sm font-bold text-slate-700" for="movement-cost-{{ $movement->id }}">Wholesale cost per unit</label>
                    <input id="movement-cost-{{ $movement->id }}" name="unit_cost" type="number" min="0" step="1" value="{{ old('_modal') === $modalName ? old('unit_cost') : (int) $movement->unit_cost }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="movement-price-{{ $movement->id }}">Customer price per unit</label>
                    <input id="movement-price-{{ $movement->id }}" name="unit_price" type="number" min="0" step="1" value="{{ old('_modal') === $modalName ? old('unit_price') : (int) $movement->unit_price }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="movement-warehouse-{{ $movement->id }}">Warehouse quantity</label>
                    <input id="movement-warehouse-{{ $movement->id }}" name="warehouse_quantity" type="number" min="0" step="1" value="{{ old('_modal') === $modalName ? old('warehouse_quantity') : $movement->warehouse_quantity_delta }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="movement-display-{{ $movement->id }}">Display quantity</label>
                    <input id="movement-display-{{ $movement->id }}" name="display_quantity" type="number" min="0" step="1" value="{{ old('_modal') === $modalName ? old('display_quantity') : $movement->display_quantity_delta }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>
            @elseif ($movement->type === \App\Models\InventoryMovement::TYPE_STOCK_INTAKE)
                <div>
                    <label class="text-sm font-bold text-slate-700" for="movement-quantity-{{ $movement->id }}">Quantity received</label>
                    <input id="movement-quantity-{{ $movement->id }}" name="quantity" type="number" min="1" step="1" value="{{ old('_modal') === $modalName ? old('quantity') : $movement->quantity }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="movement-cost-{{ $movement->id }}">Wholesale cost per unit</label>
                    <input id="movement-cost-{{ $movement->id }}" name="unit_cost" type="number" min="0" step="1" value="{{ old('_modal') === $modalName ? old('unit_cost') : (int) $movement->unit_cost }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="movement-price-{{ $movement->id }}">Customer price per unit</label>
                    <input id="movement-price-{{ $movement->id }}" name="unit_price" type="number" min="0" step="1" value="{{ old('_modal') === $modalName ? old('unit_price') : (int) $movement->unit_price }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>
            @else
                <div>
                    <label class="text-sm font-bold text-slate-700" for="movement-quantity-{{ $movement->id }}">Quantity moved</label>
                    <input id="movement-quantity-{{ $movement->id }}" name="quantity" type="number" min="1" step="1" value="{{ old('_modal') === $modalName ? old('quantity') : $movement->quantity }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>
                <div class="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-600">
                    Cost and customer price stay attached to the stock batch.
                </div>
            @endif

            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700" for="movement-notes-{{ $movement->id }}">Notes</label>
                <textarea id="movement-notes-{{ $movement->id }}" name="notes" rows="3" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('_modal') === $modalName ? old('notes') : $movement->notes }}</textarea>
            </div>
            <div class="flex flex-wrap gap-3 sm:col-span-2">
                <button class="rounded bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">Save changes</button>
                <button type="button" class="rounded border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50" @click="$dispatch('close-admin-modal')">Cancel</button>
            </div>
        </form>
    </x-admin-modal>
@endforeach
