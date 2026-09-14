@extends('website.layout')

@section('title', 'Shop School Supplies - EduKit')

@section('content')
    <section class="border-b border-[#dbe8f3] bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-[11px] font-extrabold uppercase text-emerald-700">EduKit marketplace</p>
            <h1 class="mt-2 text-[28px] font-extrabold leading-tight text-[#07215f] sm:text-[34px]">Shop school supplies</h1>

            <form method="GET" action="{{ route('website.products.index') }}" class="mt-5 grid gap-3 rounded-md border border-[#dbe8f3] bg-[#f7fbff] p-3 shadow-sm md:grid-cols-[1fr_220px_170px_auto_auto]">
                <input name="search" value="{{ $search }}" placeholder="Search products, code or brand" class="rounded-md border-[#d7e4ef] text-sm font-medium focus:border-emerald-600 focus:ring-emerald-600">
                <select name="category" class="rounded-md border-[#d7e4ef] text-sm font-medium focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected($selectedCategory === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="sort" class="rounded-md border-[#d7e4ef] text-sm font-medium focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="latest" @selected($sort === 'latest')>Newest</option>
                    <option value="price_low" @selected($sort === 'price_low')>Price: low to high</option>
                    <option value="price_high" @selected($sort === 'price_high')>Price: high to low</option>
                    <option value="name" @selected($sort === 'name')>Name</option>
                </select>
                <button class="rounded-md bg-[#07215f] px-5 py-2 text-sm font-extrabold text-white hover:bg-emerald-700">Filter</button>
                <a href="{{ route('website.products.index') }}" class="grid place-items-center rounded-md border border-[#d7e4ef] bg-white px-5 py-2 text-sm font-extrabold text-[#07215f] hover:border-emerald-500">Clear</a>
            </form>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-5 px-4 py-6 sm:px-6 lg:grid-cols-[240px_1fr] lg:px-8">
        <aside class="hidden h-fit rounded-md border border-[#dbe8f3] bg-white shadow-sm lg:block">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-black text-[#07215f]">Departments</p>
            </div>
            <nav class="grid p-2 text-[13px] font-bold text-[#173267]" aria-label="Product categories">
                <a href="{{ route('website.products.index', ['search' => $search, 'sort' => $sort]) }}" class="flex items-center justify-between rounded px-3 py-2.5 {{ $selectedCategory === '' ? 'bg-emerald-50 text-emerald-700' : 'hover:bg-slate-50 hover:text-emerald-700' }}">
                    <span>All products</span>
                    <span class="text-[11px] text-slate-400">{{ number_format($products->total()) }}</span>
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('website.products.index', ['category' => $category->slug, 'sort' => $sort]) }}" class="flex items-center justify-between rounded px-3 py-2.5 {{ $selectedCategory === $category->slug ? 'bg-emerald-50 text-emerald-700' : 'hover:bg-slate-50 hover:text-emerald-700' }}">
                        <span>{{ $category->name }}</span>
                        <span class="text-[11px] text-slate-400">{{ number_format($category->products_count) }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div>
            <div class="mb-4 flex flex-col gap-3 rounded-md border border-[#dbe8f3] bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-bold text-slate-600">
                    <span class="text-[#07215f]">{{ number_format($products->total()) }}</span> products found
                    @if ($search)
                        for <span class="text-[#07215f]">{{ $search }}</span>
                    @endif
                </p>
                <div class="no-scrollbar flex gap-2 overflow-x-auto text-xs font-black">
                    @foreach ($categories->take(6) as $category)
                        <a href="{{ route('website.products.index', ['category' => $category->slug]) }}" class="whitespace-nowrap rounded-full border border-[#d7e4ef] px-3 py-1.5 text-[#173267] hover:border-emerald-500 hover:text-emerald-700">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 min-[420px]:grid-cols-2 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
                @forelse ($products as $product)
                    <article class="group overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <a href="{{ route('website.products.show', $product) }}" class="relative block aspect-[4/3] bg-slate-100">
                            <span class="absolute left-2 top-2 z-10 rounded bg-white/95 px-2 py-1 text-[10px] font-black uppercase text-emerald-700 shadow-sm">{{ $product->category?->name ?? 'Supply' }}</span>
                            <x-ui.responsive-image
                                :src="$product->image_url"
                                :alt="$product->name"
                                :label="$product->category?->name ?? 'EduKit Supply'"
                                fallback-class="grid h-full w-full place-items-center bg-emerald-50 px-3 text-center text-xs font-black uppercase tracking-wide text-emerald-800"
                            />
                        </a>
                        <div class="p-3">
                            <a href="{{ route('website.products.show', $product) }}" class="line-clamp-2 block min-h-10 text-[14px] font-extrabold leading-5 text-[#07215f] group-hover:text-emerald-700">{{ $product->name }}</a>
                            <p class="mt-3 text-[16px] font-black text-slate-950">UGX {{ number_format($product->price) }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-slate-500">{{ number_format($product->stock_quantity) }} available</p>
                            <div class="mt-4 grid grid-cols-[1fr_1.15fr] gap-2">
                                <a href="{{ route('website.products.show', $product) }}" class="grid min-h-10 place-items-center rounded-md border border-[#d7e4ef] px-3 py-2 text-xs font-extrabold text-[#07215f] hover:border-emerald-500">View</a>
                                @if ($product->stock_quantity > 0)
                                    <form method="POST" action="{{ route('website.cart.store', $product) }}" class="grid grid-cols-[54px_1fr] gap-2">
                                        @csrf
                                        <label class="sr-only" for="product-quantity-{{ $product->id }}">Quantity</label>
                                        <input id="product-quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ $product->stock_quantity }}" value="1" class="h-10 w-full rounded-md border-[#d7e4ef] text-center text-xs font-bold text-[#07215f] focus:border-emerald-600 focus:ring-emerald-600">
                                        <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-xs font-extrabold text-white hover:bg-emerald-700">Add</button>
                                    </form>
                                @else
                                    <span class="grid min-h-10 place-items-center rounded-md bg-slate-100 px-3 py-2 text-xs font-extrabold text-slate-500">Out of stock</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="border border-dashed border-slate-300 bg-white p-8 min-[420px]:col-span-2 xl:col-span-4">
                        <p class="font-semibold text-slate-950">No products match this search.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        </div>
    </section>
@endsection
