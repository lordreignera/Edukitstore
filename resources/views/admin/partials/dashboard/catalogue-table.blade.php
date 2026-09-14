<section class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm xl:col-span-2">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="text-sm font-extrabold text-slate-950">Latest Products</h3>
            <p class="mt-0.5 text-xs text-slate-500">Recently added items and display availability</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-700 hover:text-emerald-700">
            View all <x-ui.icon name="arrow-right" size="size-3.5" />
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-[11px] font-bold uppercase text-slate-500">
                <tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Price</th><th class="px-5 py-3">Display</th><th class="px-5 py-3">Warehouse</th><th class="px-5 py-3">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($latestProducts as $product)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-3.5">
                            <div class="flex min-w-[210px] items-center gap-3">
                                <div class="size-10 shrink-0 overflow-hidden rounded border border-slate-200 bg-slate-50">
                                    <x-ui.responsive-image
                                        :src="$product->image_url"
                                        alt=""
                                        label="EK"
                                        image-class="h-full w-full object-contain p-1"
                                        fallback-class="grid h-full w-full place-items-center text-[10px] font-black text-slate-400"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="block truncate font-bold text-slate-900 hover:text-emerald-700">{{ $product->name }}</a>
                                    <p class="truncate text-xs text-slate-500">{{ $product->category?->name ?? 'Uncategorised' }} &middot; {{ $product->sku }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 font-bold text-slate-800">UGX {{ number_format($product->price) }}</td>
                        <td class="px-5 py-3.5 text-slate-600">{{ number_format($product->stock_quantity) }}</td>
                        <td class="px-5 py-3.5 text-slate-600">{{ number_format($product->warehouse_stock_quantity) }}</td>
                        <td class="px-5 py-3.5"><span class="inline-flex rounded-full px-2 py-1 text-[11px] font-bold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $product->is_active ? 'Live' : 'Hidden' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">No products have been added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
