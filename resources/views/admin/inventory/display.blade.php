<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-orange-700">Inventory / EduKit display</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Display Stock</h1>
                <p class="mt-1 text-sm text-slate-500">EduKit-owned units on display. Active catalogue products may also have separate supplier stock online.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.inventory.warehouse') }}" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Open warehouse stock</a>
                <a href="{{ route('admin.inventory.index') }}" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-orange-300 px-4 py-2 text-sm font-bold text-orange-800 hover:bg-orange-50">Stock overview</a>
            </div>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            @if (session('status'))
                <div class="rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-bold text-orange-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
            @endif

            <section class="grid gap-4 sm:grid-cols-3" aria-label="Display stock summary">
                <article class="rounded-md border border-emerald-200 bg-white p-4"><p class="text-xs font-bold uppercase text-emerald-800">EduKit display units</p><p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['units']) }}</p><p class="mt-1 text-xs text-slate-500">All products; table filters do not change this total</p></article>
                <article class="rounded-md border border-amber-200 bg-amber-50 p-4"><p class="text-xs font-bold uppercase text-amber-800">Product lines at/below reorder</p><p class="mt-2 text-3xl font-extrabold text-amber-950">{{ number_format($stats['low_products']) }}</p><p class="mt-1 text-xs text-amber-800">Current counts for the full catalogue</p></article>
                <article class="rounded-md border border-red-200 bg-red-50 p-4"><p class="text-xs font-bold uppercase text-red-800">Zero display units</p><p class="mt-2 text-3xl font-extrabold text-red-950">{{ number_format($stats['empty_products']) }}</p><p class="mt-1 text-xs text-red-800">Product lines with zero EduKit display count</p></article>
            </section>

            <form method="GET" action="{{ route('admin.inventory.display') }}" class="filter-bar">
                <input name="q" value="{{ $search }}" maxlength="100" aria-label="Search display stock products" placeholder="Search product, SKU or brand" class="h-10 min-w-0 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                <select name="category" class="h-10 min-w-0 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600"><option value="">All categories</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected($categoryId === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
                <button class="h-10 rounded bg-[#07215f] px-5 text-sm font-bold text-white hover:bg-orange-700">Filter</button>
                <a href="{{ route('admin.inventory.display') }}" class="grid h-10 place-items-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Clear</a>
            </form>

            <section class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-extrabold text-slate-950">EduKit display stock by product</h2><p class="mt-1 text-xs text-slate-500">This is EduKit's stock ledger, not total marketplace availability. Hidden products are not listed in the catalogue.</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase text-slate-500"><tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Display stock</th><th class="px-5 py-3">Reorder level</th><th class="px-5 py-3">Warehouse</th><th class="px-5 py-3">Customer price</th><th class="px-5 py-3">Catalogue status</th><th class="px-5 py-3">Actions</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($products as $product)
                                @php($isLow = $product->stock_quantity <= $product->reorder_level)
                                <tr>
                                    <td class="px-5 py-4"><p class="font-bold text-slate-950">{{ $product->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $product->category?->name ?? 'Uncategorised' }} · {{ $product->sku }}</p></td>
                                    <td class="px-5 py-4">
                                        <span class="font-extrabold {{ $isLow ? 'text-red-700' : 'text-emerald-700' }}">{{ number_format($product->stock_quantity) }}</span>
                                        <span class="ml-1 text-xs text-slate-500">{{ $product->stock_quantity === 0 ? 'none' : ($isLow ? 'low' : 'on display') }}</span>
                                        @if ($product->display_batch_quantity !== (int) $product->stock_quantity)
                                            <span class="mt-1 block max-w-xs text-xs font-semibold text-amber-800">Ledger mismatch: product count {{ number_format($product->stock_quantity) }}, batch quantity {{ number_format($product->display_batch_quantity) }}. Checkout is limited to the lower quantity.</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-slate-700">{{ number_format($product->reorder_level) }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-700">{{ number_format($product->warehouse_stock_quantity) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">UGX {{ number_format($product->price) }}</td>
                                    <td class="px-5 py-4"><span class="inline-flex rounded border px-2 py-1 text-xs font-bold {{ $product->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-100 text-slate-600' }}">{{ $product->is_active ? 'Active listing' : 'Hidden listing' }}</span></td>
                                    <td class="px-5 py-4"><div class="flex min-w-[180px] flex-wrap gap-2">
                                        @can('inventory.manage')
                                            <button type="button" @click="$dispatch('open-admin-modal', 'transfer-product-{{ $product->id }}')" @disabled($product->transferable_warehouse_quantity < 1) class="rounded border border-orange-300 px-3 py-2 text-xs font-bold text-orange-800 hover:bg-orange-50 disabled:cursor-not-allowed disabled:opacity-50">Move to display</button>
                                        @endcan
                                        @can('products.view')<a href="{{ route('admin.products.show', $product) }}" class="rounded border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Product details</a>@endcan
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No products match this search.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $products->links() }}</div>
            </section>
        </div>
    </div>

    @push('modals')
        @include('admin.inventory.partials.operation-modals')
        @if ($errors->any() && old('_modal'))<div x-data x-init="$nextTick(() => $dispatch('open-admin-modal', '{{ old('_modal') }}'))"></div>@endif
    @endpush
</x-admin-layout>
