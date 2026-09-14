<section class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="text-sm font-extrabold text-slate-950">Products on Display and in Warehouse</h2>
        <p class="mt-1 text-xs text-slate-500">When display stock is low, move quantity from warehouse. If warehouse is empty too, prepare to buy.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Product</th>
                    <th class="px-5 py-3">Pricing</th>
                    <th class="px-5 py-3">Stock</th>
                    <th class="px-5 py-3">Order flow</th>
                    <th class="px-5 py-3">Profit</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    <tr class="align-top">
                        <td class="px-5 py-4">
                            <div class="flex min-w-[280px] gap-3">
                                <div class="size-16 shrink-0 overflow-hidden rounded border border-slate-200 bg-slate-50">
                                    <x-ui.responsive-image
                                        :src="$product->image_url"
                                        :alt="$product->name"
                                        label="EK"
                                        image-class="h-full w-full object-contain p-1"
                                        fallback-class="grid h-full w-full place-items-center text-[10px] font-black text-slate-400"
                                    />
                                </div>
                                <div>
                                    <p class="font-black text-slate-950">{{ $product->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $product->category?->name ?? 'Uncategorised' }} | {{ $product->sku }}</p>
                                    @if ($product->stock_quantity <= $product->reorder_level)
                                        <span class="mt-2 inline-flex rounded bg-amber-50 px-2 py-1 text-[11px] font-bold text-amber-700">Low display stock</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <p class="whitespace-nowrap text-xs font-bold text-slate-500">Cost: <span class="text-slate-900">UGX {{ number_format($product->cost_price) }}</span></p>
                            <p class="mt-1 whitespace-nowrap text-xs font-bold text-slate-500">Customer: <span class="text-slate-900">UGX {{ number_format($product->price) }}</span></p>
                            <p class="mt-1 whitespace-nowrap text-xs font-bold text-emerald-700">Margin: UGX {{ number_format($product->profit_per_unit) }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-slate-900">{{ number_format($product->stock_quantity) }} display</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ number_format($product->warehouse_stock_quantity) }} warehouse</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ number_format($product->reorder_level) }} reorder level</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-xs font-semibold text-slate-500">{{ number_format($product->ordered_units) }} awaiting payment</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ number_format($product->paid_pending_units) }} paid, needs driver</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ number_format($product->in_transit_units) }} in transit</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ number_format($product->sold_units ?? 0) }} sold</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="whitespace-nowrap font-black text-emerald-700">UGX {{ number_format($product->gross_profit ?? 0) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $product->gross_margin_percentage }}% current margin</p>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex min-w-[210px] flex-wrap justify-end gap-2">
                                <button type="button" @click="$dispatch('open-admin-modal', 'intake-product-{{ $product->id }}')" class="rounded bg-emerald-700 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-800">Stock intake</button>
                                <button type="button" @click="$dispatch('open-admin-modal', 'transfer-product-{{ $product->id }}')" @disabled($product->warehouse_stock_quantity < 1) class="rounded border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Move to display</button>
                                <a href="{{ route('admin.products.show', $product) }}" class="rounded border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">View</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-500">No inventory products match this search.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-5 py-4">
        {{ $products->links() }}
    </div>
</section>
