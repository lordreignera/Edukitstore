@foreach ($products as $product)
    <x-admin-modal name="opening-product-{{ $product->id }}" title="Opening stock" description="Record the starting balance for {{ $product->name }} before daily operations begin.">
        <form method="POST" action="{{ route('admin.inventory.opening', $product) }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <input type="hidden" name="_modal" value="opening-product-{{ $product->id }}">
            <div>
                <label class="text-sm font-bold text-slate-700" for="opening-date-{{ $product->id }}">Opening date</label>
                <input id="opening-date-{{ $product->id }}" name="occurred_at" type="date" value="{{ old('_modal') === 'opening-product-'.$product->id ? old('occurred_at') : now()->toDateString() }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="opening-cost-{{ $product->id }}">Wholesale cost per unit</label>
                <input id="opening-cost-{{ $product->id }}" name="unit_cost" type="number" min="0" step="1" value="{{ old('_modal') === 'opening-product-'.$product->id ? old('unit_cost') : (int) $product->cost_price }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="opening-price-{{ $product->id }}">Customer price per unit</label>
                <input id="opening-price-{{ $product->id }}" name="unit_price" type="number" min="0" step="1" value="{{ old('_modal') === 'opening-product-'.$product->id ? old('unit_price') : (int) $product->price }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="opening-warehouse-{{ $product->id }}">Warehouse quantity</label>
                <input id="opening-warehouse-{{ $product->id }}" name="warehouse_quantity" type="number" min="0" step="1" value="{{ old('_modal') === 'opening-product-'.$product->id ? old('warehouse_quantity') : 0 }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="opening-display-{{ $product->id }}">Website/display quantity</label>
                <input id="opening-display-{{ $product->id }}" name="display_quantity" type="number" min="0" step="1" value="{{ old('_modal') === 'opening-product-'.$product->id ? old('display_quantity') : 0 }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700" for="opening-notes-{{ $product->id }}">Notes</label>
                <textarea id="opening-notes-{{ $product->id }}" name="notes" rows="3" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('_modal') === 'opening-product-'.$product->id ? old('notes') : '' }}</textarea>
            </div>
            <div class="flex flex-wrap gap-3 sm:col-span-2">
                <button class="rounded bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">Save opening stock</button>
                <button type="button" class="rounded border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50" @click="$dispatch('close-admin-modal')">Cancel</button>
            </div>
        </form>
    </x-admin-modal>

    <x-admin-modal name="intake-product-{{ $product->id }}" title="Stock intake" description="Add newly bought stock into the warehouse for {{ $product->name }}.">
        <form method="POST" action="{{ route('admin.inventory.intake', $product) }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <input type="hidden" name="_modal" value="intake-product-{{ $product->id }}">
            <div>
                <label class="text-sm font-bold text-slate-700" for="intake-date-{{ $product->id }}">Intake date</label>
                <input id="intake-date-{{ $product->id }}" name="occurred_at" type="date" value="{{ old('_modal') === 'intake-product-'.$product->id ? old('occurred_at') : now()->toDateString() }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="intake-quantity-{{ $product->id }}">Quantity received</label>
                <input id="intake-quantity-{{ $product->id }}" name="quantity" type="number" min="1" value="{{ old('_modal') === 'intake-product-'.$product->id ? old('quantity') : 1 }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="intake-cost-{{ $product->id }}">Wholesale cost per unit</label>
                <input id="intake-cost-{{ $product->id }}" name="unit_cost" type="number" min="0" step="1" value="{{ old('_modal') === 'intake-product-'.$product->id ? old('unit_cost') : (int) $product->cost_price }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="intake-price-{{ $product->id }}">Customer price per unit</label>
                <input id="intake-price-{{ $product->id }}" name="unit_price" type="number" min="0" step="1" value="{{ old('_modal') === 'intake-product-'.$product->id ? old('unit_price') : (int) $product->price }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div>
                <p class="text-sm font-bold text-slate-700">Current warehouse</p>
                <p class="mt-2 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-black text-slate-950">{{ number_format($product->warehouse_stock_quantity) }} units</p>
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700" for="intake-notes-{{ $product->id }}">Notes</label>
                <textarea id="intake-notes-{{ $product->id }}" name="notes" rows="3" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('_modal') === 'intake-product-'.$product->id ? old('notes') : '' }}</textarea>
            </div>
            <div class="flex flex-wrap gap-3 sm:col-span-2">
                <button class="rounded bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">Save intake</button>
                <button type="button" class="rounded border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50" @click="$dispatch('close-admin-modal')">Cancel</button>
            </div>
        </form>
    </x-admin-modal>

    <x-admin-modal name="transfer-product-{{ $product->id }}" title="Move stock to website/display" description="Make warehouse stock available for parents to add to cart.">
        <form method="POST" action="{{ route('admin.inventory.transfer', $product) }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <input type="hidden" name="_modal" value="transfer-product-{{ $product->id }}">
            <div>
                <label class="text-sm font-bold text-slate-700" for="transfer-date-{{ $product->id }}">Transfer date</label>
                <input id="transfer-date-{{ $product->id }}" name="occurred_at" type="date" value="{{ old('_modal') === 'transfer-product-'.$product->id ? old('occurred_at') : now()->toDateString() }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div>
                <label class="text-sm font-bold text-slate-700" for="transfer-quantity-{{ $product->id }}">Quantity to move</label>
                <input id="transfer-quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ $product->warehouse_stock_quantity }}" value="{{ old('_modal') === 'transfer-product-'.$product->id ? old('quantity') : min(1, $product->warehouse_stock_quantity) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            </div>
            <div class="grid gap-2 text-sm">
                <p class="rounded border border-slate-200 bg-slate-50 px-3 py-2 font-bold text-slate-700">Warehouse: <span class="text-slate-950">{{ number_format($product->warehouse_stock_quantity) }}</span></p>
                <p class="rounded border border-slate-200 bg-slate-50 px-3 py-2 font-bold text-slate-700">Display: <span class="text-slate-950">{{ number_format($product->stock_quantity) }}</span></p>
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-bold text-slate-700" for="transfer-notes-{{ $product->id }}">Notes</label>
                <textarea id="transfer-notes-{{ $product->id }}" name="notes" rows="3" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('_modal') === 'transfer-product-'.$product->id ? old('notes') : '' }}</textarea>
            </div>
            <div class="flex flex-wrap gap-3 sm:col-span-2">
                <button class="rounded bg-[#07215f] px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Move stock</button>
                <button type="button" class="rounded border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50" @click="$dispatch('close-admin-modal')">Cancel</button>
            </div>
        </form>
    </x-admin-modal>
@endforeach
