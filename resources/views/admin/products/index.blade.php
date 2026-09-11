<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Master Product List</h2>
                <p class="mt-1 text-sm text-gray-500">Super admin sets customer-facing prices for the website catalogue.</p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Add product</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_320px] lg:px-8">
            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    @if (session('status'))
                        <p class="text-sm font-medium text-emerald-700">{{ session('status') }}</p>
                    @else
                        <p class="text-sm text-gray-500">Products marked active appear on the public website.</p>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Product</th>
                                <th class="px-5 py-3">SKU</th>
                                <th class="px-5 py-3">Price</th>
                                <th class="px-5 py-3">Stock</th>
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
                                    <td class="px-5 py-4 font-semibold text-gray-950">UGX {{ number_format($product->price) }}</td>
                                    <td class="px-5 py-4 text-gray-600">{{ $product->stock_quantity }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded px-2 py-1 text-xs font-semibold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $product->is_active ? 'Active' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold text-emerald-700">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-500">No products have been added.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $products->links() }}
                </div>
            </section>

            <aside class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-gray-950">Add Category</h3>
                <form method="POST" action="{{ route('admin.product-categories.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="category-name">Name</label>
                        <input id="category-name" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="category-description">Description</label>
                        <textarea id="category-description" name="description" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('description') }}</textarea>
                    </div>
                    <button class="w-full rounded bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Save category</button>
                </form>
            </aside>
        </div>
    </div>
</x-app-layout>
