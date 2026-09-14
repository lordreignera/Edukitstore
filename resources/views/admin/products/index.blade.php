<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Master Product List</h2>
                <p class="mt-1 text-sm text-gray-500">Manage catalogue details, customer prices and stock visible on the website.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-2 rounded border border-blue-700 px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50">
                    <x-ui.icon name="warehouse" size="size-4" /> Inventory
                </a>
                <a href="{{ route('admin.product-categories.create') }}" class="inline-flex items-center gap-2 rounded border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">
                    <x-ui.icon name="tag" size="size-4" /> Add category
                </a>
                <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                    <x-ui.icon name="plus" size="size-4" /> Add product
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" class="mb-5 grid gap-3 border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-[minmax(180px,1fr)_170px_140px_140px_auto_auto]">
                <input name="q" value="{{ $search }}" placeholder="Search name, product code or brand" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                <select name="category" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"><option value="">All categories</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected($categoryId === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
                <select name="status" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"><option value="">Any status</option><option value="active" @selected($status === 'active')>Active</option><option value="hidden" @selected($status === 'hidden')>Hidden</option></select>
                <select name="featured" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"><option value="">Featured or not</option><option value="yes" @selected($featured === 'yes')>Featured</option><option value="no" @selected($featured === 'no')>Not featured</option></select>
                <button class="h-10 rounded bg-[#07215f] px-4 text-sm font-bold text-white hover:bg-[#0b2f7c]">Filter</button>
                <a href="{{ route('admin.products.index') }}" class="grid h-10 place-items-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Clear</a>
            </form>
            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    @if (session('status'))
                        <p class="text-sm font-medium text-emerald-700">{{ session('status') }}</p>
                    @else
                        <p class="text-sm text-gray-500">Website/display stock is the quantity parents can buy. Warehouse stock is moved through the inventory module.</p>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Product</th>
                                <th class="px-5 py-3">Product code</th>
                                <th class="px-5 py-3">Cost</th>
                                <th class="px-5 py-3">Customer price</th>
                                <th class="px-5 py-3">Display</th>
                                <th class="px-5 py-3">Warehouse</th>
                                <th class="px-5 py-3">Profit/unit</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($products as $product)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-950">{{ $product->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $product->category?->name ?? 'Uncategorised' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $product->sku }}</td>
                                    <td class="px-5 py-4 font-semibold text-gray-700">UGX {{ number_format($product->cost_price) }}</td>
                                    <td class="px-5 py-4 font-semibold text-gray-950">UGX {{ number_format($product->price) }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-950">{{ number_format($product->stock_quantity) }}</p>
                                        @if ($product->stock_quantity <= $product->reorder_level)
                                            <p class="mt-1 text-[11px] font-bold text-amber-700">Low display stock</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ number_format($product->warehouse_stock_quantity) }}</td>
                                    <td class="px-5 py-4 font-semibold text-emerald-700">UGX {{ number_format($product->profit_per_unit) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded px-2 py-1 text-xs font-semibold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $product->is_active ? 'Active' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.products.show', $product) }}" class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700" title="View product" aria-label="View {{ $product->name }}">
                                                <x-ui.icon name="eye" size="size-4" />
                                            </a>
                                            <a href="{{ route('admin.products.edit', $product) }}" class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" title="Edit product" aria-label="Edit {{ $product->name }}">
                                                <x-ui.icon name="edit" size="size-4" />
                                            </a>
                                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product from the master list?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-red-200 hover:bg-red-50 hover:text-red-700" title="Delete product" aria-label="Delete {{ $product->name }}">
                                                    <x-ui.icon name="trash" size="size-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-8 text-center text-gray-500">No products have been added.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $products->links() }}
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
