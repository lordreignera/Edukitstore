<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-orange-700">Inventory / Warehouse</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Warehouse Stock</h1>
                <p class="mt-1 text-sm text-slate-500">Stock in storage. It is not available to customers until moved to display stock.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.inventory.index') }}" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Stock overview</a>
                @can('inventory.import')
                    <button type="button" @click="$dispatch('open-admin-modal', 'import-inventory')" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-orange-300 px-4 py-2 text-sm font-bold text-orange-800 hover:bg-orange-50"><x-ui.icon name="upload" size="size-4" /> Import CSV</button>
                @endcan
                @can('inventory.export')
                    <a href="{{ route('admin.inventory.export') }}" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-orange-300 px-4 py-2 text-sm font-bold text-orange-800 hover:bg-orange-50"><x-ui.icon name="download" size="size-4" /> Export CSV</a>
                @endcan
                @can('products.create')
                    <a href="{{ route('admin.products.create') }}" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-orange-700 px-4 py-2 text-sm font-bold text-white hover:bg-orange-800"><x-ui.icon name="plus" size="size-4" /> Add product</a>
                @endcan
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

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Warehouse stock summary">
                <article class="rounded-md border border-orange-200 bg-white p-4"><p class="text-xs font-bold uppercase text-orange-800">Warehouse units</p><p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['units']) }}</p><p class="mt-1 text-xs text-slate-500">All products; table filters do not change this total</p></article>
                <article class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase text-slate-500">Products with stock</p><p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['products']) }}</p><p class="mt-1 text-xs text-slate-500">Product lines across the full catalogue</p></article>
                <article class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase text-slate-500">Costed warehouse value</p><p class="mt-2 text-2xl font-extrabold text-slate-950">UGX {{ number_format($stats['stock_value']) }}</p><p class="mt-1 text-xs text-slate-500">Remaining batch quantities at recorded cost, all products</p></article>
                <article class="rounded-md border border-red-200 bg-red-50 p-4"><p class="text-xs font-bold uppercase text-red-800">Empty warehouse lines</p><p class="mt-2 text-3xl font-extrabold text-red-900">{{ number_format($stats['empty_products']) }}</p><p class="mt-1 text-xs text-red-700">Product lines across the full catalogue</p></article>
            </section>

            <form method="GET" action="{{ route('admin.inventory.warehouse') }}" class="filter-bar">
                <input name="q" value="{{ $search }}" maxlength="100" aria-label="Search warehouse products" placeholder="Search product, SKU or brand" class="h-10 min-w-0 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                <select name="category" class="h-10 min-w-0 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600"><option value="">All categories</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected($categoryId === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
                <button class="h-10 rounded bg-[#07215f] px-5 text-sm font-bold text-white hover:bg-orange-700">Filter</button>
                <a href="{{ route('admin.inventory.warehouse') }}" class="grid h-10 place-items-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Clear</a>
            </form>

            <section class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-extrabold text-slate-950">Warehouse by product</h2><p class="mt-1 text-xs text-slate-500">Receive new stock here, then move units to display when they should be purchasable online.</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase text-slate-500"><tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Warehouse quantity</th><th class="px-5 py-3">Display quantity</th><th class="px-5 py-3">Average warehouse batch cost</th><th class="px-5 py-3">Actions</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($products as $product)
                                @php($averageWarehouseCost = $product->warehouse_batch_quantity > 0 ? $product->warehouse_batch_cost_value / $product->warehouse_batch_quantity : null)
                                <tr>
                                    <td class="px-5 py-4"><p class="font-bold text-slate-950">{{ $product->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $product->category?->name ?? 'Uncategorised' }} · {{ $product->sku }}</p></td>
                                    <td class="px-5 py-4"><span class="font-extrabold {{ $product->warehouse_stock_quantity > 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($product->warehouse_stock_quantity) }}</span><span class="ml-1 text-xs text-slate-500">units</span></td>
                                    <td class="px-5 py-4 font-semibold text-slate-700">{{ number_format($product->stock_quantity) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">{{ $averageWarehouseCost === null ? 'Uncosted' : 'UGX '.number_format($averageWarehouseCost) }}</td>
                                    <td class="px-5 py-4"><div class="flex min-w-[250px] flex-wrap gap-2">
                                        @if ($product->warehouse_batch_quantity !== (int) $product->warehouse_stock_quantity)
                                            <span class="w-full text-xs font-semibold text-amber-800">Stock count ({{ number_format($product->warehouse_stock_quantity) }}) differs from costed batches ({{ number_format($product->warehouse_batch_quantity) }}).</span>
                                        @endif
                                        @can('inventory.manage')
                                            @if (! $product->has_opening_stock)<button type="button" @click="$dispatch('open-admin-modal', 'opening-product-{{ $product->id }}')" class="rounded border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Opening stock</button>@endif
                                            <button type="button" @click="$dispatch('open-admin-modal', 'intake-product-{{ $product->id }}')" class="rounded bg-orange-700 px-3 py-2 text-xs font-bold text-white hover:bg-orange-800">Receive stock</button>
                                            <button type="button" @click="$dispatch('open-admin-modal', 'transfer-product-{{ $product->id }}')" @disabled($product->transferable_warehouse_quantity < 1) class="rounded border border-orange-300 px-3 py-2 text-xs font-bold text-orange-800 hover:bg-orange-50 disabled:cursor-not-allowed disabled:opacity-50">Move to display</button>
                                        @endcan
                                        @can('products.view')<a href="{{ route('admin.products.show', $product) }}" class="rounded border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Product details</a>@endcan
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No products match this search.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $products->links() }}</div>
            </section>
        </div>
    </div>

    @push('modals')
        @can('inventory.import')
            @include('admin.inventory.partials.import-modal')
        @endcan
        @include('admin.inventory.partials.operation-modals')
        @if ($errors->any() && old('_modal'))<div x-data x-init="$nextTick(() => $dispatch('open-admin-modal', '{{ old('_modal') }}'))"></div>@endif
    @endpush
</x-admin-layout>
