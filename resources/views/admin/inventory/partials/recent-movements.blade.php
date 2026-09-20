<section class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="text-sm font-extrabold text-slate-950">Inventory Movement History</h2>
        <p class="mt-1 text-xs text-slate-500">Opening stock, intake, display transfers and paid sales for the selected period.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">Movement</th>
                    <th class="px-5 py-3">Stock impact</th>
                    <th class="px-5 py-3">Money</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($movements as $movement)
                    <tr class="align-top">
                        <td class="whitespace-nowrap px-5 py-4 text-xs font-semibold text-slate-600">
                            {{ $movement->occurred_at->format('d M Y') }}
                            <p class="mt-1 text-[11px] text-slate-400">{{ $movement->created_at->format('H:i') }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-black text-slate-950">{{ $movement->product_name ?? $movement->product?->name ?? 'Deleted product' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $movement->sku ?? 'No code' }}</p>
                            @if ($movement->inventoryBatch)
                                <p class="mt-1 text-[11px] font-semibold text-slate-400">{{ $movement->inventoryBatch->batch_reference }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-slate-900">{{ $movementLabels[$movement->type] ?? str($movement->type)->headline() }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $movement->from_location ?: '-' }} to {{ $movement->to_location ?: '-' }}</p>
                            @if ($movement->notes)
                                <p class="mt-1 max-w-xs text-xs leading-5 text-slate-500">{{ $movement->notes }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-xs font-semibold text-slate-600">Qty: <span class="text-slate-950">{{ number_format($movement->quantity) }}</span></p>
                            <p class="mt-1 text-xs font-semibold text-slate-600">Warehouse: <span class="{{ $movement->warehouse_quantity_delta < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $movement->warehouse_quantity_delta > 0 ? '+' : '' }}{{ number_format($movement->warehouse_quantity_delta) }}</span></p>
                            <p class="mt-1 text-xs font-semibold text-slate-600">Display: <span class="{{ $movement->display_quantity_delta < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $movement->display_quantity_delta > 0 ? '+' : '' }}{{ number_format($movement->display_quantity_delta) }}</span></p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="whitespace-nowrap text-xs font-semibold text-slate-600">Cost: <span class="text-slate-950">UGX {{ number_format($movement->total_cost) }}</span></p>
                            <p class="mt-1 whitespace-nowrap text-xs font-semibold text-slate-600">Revenue: <span class="text-slate-950">UGX {{ number_format($movement->total_revenue) }}</span></p>
                            <p class="mt-1 whitespace-nowrap text-xs font-bold text-emerald-700">Profit: UGX {{ number_format($movement->profit) }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex min-w-[120px] justify-end gap-2">
                                @if ($movement->isEditable())
                                    <button type="button" @click="$dispatch('open-admin-modal', 'edit-movement-{{ $movement->id }}')" class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700" title="Edit movement" aria-label="Edit movement">
                                        <x-ui.icon name="edit" size="size-4" />
                                    </button>
                                    <form method="POST" action="{{ route('admin.inventory.movements.destroy', $movement) }}" onsubmit="return confirm('Delete this inventory movement?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-red-200 hover:bg-red-50 hover:text-red-700" title="Delete movement" aria-label="Delete movement">
                                            <x-ui.icon name="trash" size="size-4" />
                                        </button>
                                    </form>
                                @else
                                    <span class="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-500">Locked</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-500">Inventory movements will appear after opening stock, stock intake, transfers or paid sales.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-5 py-4">
        {{ $movements->links() }}
    </div>
</section>
