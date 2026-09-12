<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold text-emerald-700">Category Details</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">{{ $category->name }}</h1>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.product-categories.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back to categories</a>
                <a href="{{ route('admin.product-categories.edit', $category) }}" class="inline-flex items-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">
                    <x-ui.icon name="edit" size="size-4" /> Edit category
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-[360px_1fr] lg:px-8">
            <section class="rounded border border-slate-200 bg-white p-5 shadow-sm">
                <dl class="space-y-5">
                    <div>
                        <dt class="text-xs font-bold uppercase text-slate-500">Slug</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $category->slug }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-slate-500">Status</dt>
                        <dd class="mt-1">
                            <span class="rounded px-2 py-1 text-xs font-bold {{ $category->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $category->is_active ? 'Active' : 'Hidden' }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-slate-500">Products</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($category->products_count) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase text-slate-500">Description</dt>
                        <dd class="mt-2 text-sm leading-6 text-slate-700">{{ $category->description ?: 'No description has been added.' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-lg font-extrabold text-slate-950">Recent Products</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest items assigned to this category.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($category->products as $product)
                        <a href="{{ route('admin.products.show', $product) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50">
                            <span>
                                <span class="block text-sm font-bold text-slate-950">{{ $product->name }}</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ $product->sku }}</span>
                            </span>
                            <span class="text-sm font-bold text-emerald-700">UGX {{ number_format($product->price) }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-8 text-sm text-slate-500">No products are currently assigned to this category.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
