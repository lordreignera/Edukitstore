<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold text-emerald-700">Product Details</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">{{ $product->name }}</h1>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.products.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back to products</a>
                <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex items-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">
                    <x-ui.icon name="edit" size="size-4" /> Edit product
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-[380px_1fr] lg:px-8">
            <section class="rounded border border-slate-200 bg-white p-4 shadow-sm">
                <div class="aspect-square overflow-hidden rounded bg-slate-50">
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-contain">
                    @else
                        <div class="grid h-full place-items-center text-sm font-bold text-slate-400">No image</div>
                    @endif
                </div>
            </section>

            <section class="rounded border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-lg font-extrabold text-slate-950">Catalogue Information</h2>
                    <p class="mt-1 text-sm text-slate-500">These details power the public product listing.</p>
                </div>
                <dl class="grid gap-px bg-slate-100 sm:grid-cols-2">
                    <div class="bg-white p-5">
                        <dt class="text-xs font-bold uppercase text-slate-500">Product code</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $product->sku }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-xs font-bold uppercase text-slate-500">Category</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $product->category?->name ?? 'Uncategorised' }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-xs font-bold uppercase text-slate-500">Price</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">UGX {{ number_format($product->price) }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-xs font-bold uppercase text-slate-500">Stock</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($product->stock_quantity) }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-xs font-bold uppercase text-slate-500">Brand</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $product->brand ?: 'Not set' }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-xs font-bold uppercase text-slate-500">Unit</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $product->unit ?: 'Not set' }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-xs font-bold uppercase text-slate-500">Website Status</dt>
                        <dd class="mt-1">
                            <span class="rounded px-2 py-1 text-xs font-bold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $product->is_active ? 'Active' : 'Hidden' }}</span>
                        </dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-xs font-bold uppercase text-slate-500">Featured</dt>
                        <dd class="mt-1">
                            <span class="rounded px-2 py-1 text-xs font-bold {{ $product->is_featured ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $product->is_featured ? 'Featured' : 'Not featured' }}</span>
                        </dd>
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <dt class="text-xs font-bold uppercase text-slate-500">Description</dt>
                        <dd class="mt-2 text-sm leading-6 text-slate-700">{{ $product->description ?: 'No description has been added.' }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</x-admin-layout>
