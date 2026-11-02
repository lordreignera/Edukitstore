@extends('website.layout')

@section('title', 'Shop School Supplies - EduKit')

@section('content')
    <section class="border-b border-[#dbe8f3] bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <p class="text-[11px] font-extrabold uppercase text-orange-700">EduKit marketplace</p>
            <h1 class="mt-2 text-[28px] font-extrabold leading-tight text-[#07215f] sm:text-[34px]">Shop school supplies</h1>

            <form id="product-filter-form" method="GET" action="{{ route('website.products.index') }}" class="mt-5 grid grid-cols-2 gap-2 rounded-md border border-[#dbe8f3] bg-[#f7fbff] p-3 shadow-sm sm:gap-3 md:grid-cols-[minmax(0,1fr)_220px_170px_auto_auto]">
                <input name="search" value="{{ $search }}" maxlength="100" autocomplete="off" placeholder="Search products, brand or colour" class="col-span-2 min-w-0 rounded-md border-[#d7e4ef] text-sm font-medium focus:border-orange-600 focus:ring-orange-600 md:col-span-1">
                <select name="category" class="min-w-0 rounded-md border-[#d7e4ef] text-sm font-medium focus:border-orange-600 focus:ring-orange-600">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected($selectedCategory === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="sort" class="min-w-0 rounded-md border-[#d7e4ef] text-sm font-medium focus:border-orange-600 focus:ring-orange-600">
                    <option value="latest" @selected($sort === 'latest')>Newest</option>
                    <option value="price_low" @selected($sort === 'price_low')>Price: low to high</option>
                    <option value="price_high" @selected($sort === 'price_high')>Price: high to low</option>
                    <option value="name" @selected($sort === 'name')>Name</option>
                </select>
                <button class="min-w-0 rounded-md bg-[#07215f] px-3 py-2 text-sm font-extrabold text-white hover:bg-orange-700 sm:px-5">Filter</button>
                <a href="{{ route('website.products.index') }}" class="grid min-w-0 place-items-center rounded-md border border-[#d7e4ef] bg-white px-3 py-2 text-sm font-extrabold text-[#07215f] hover:border-orange-500 sm:px-5">Clear</a>
            </form>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-5 px-4 py-6 sm:px-6 lg:grid-cols-[240px_1fr] lg:px-8">
        <aside class="hidden h-fit rounded-md border border-[#dbe8f3] bg-white shadow-sm lg:block">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-black text-[#07215f]">Departments</p>
            </div>
            <nav class="grid p-2 text-[13px] font-bold text-[#173267]" aria-label="Product categories">
                <a href="{{ route('website.products.index', ['search' => $search, 'sort' => $sort]) }}" class="flex items-center justify-between rounded px-3 py-2.5 {{ $selectedCategory === '' ? 'bg-orange-50 text-orange-700' : 'hover:bg-slate-50 hover:text-orange-700' }}">
                    <span>All products</span>
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('website.products.index', ['category' => $category->slug, 'sort' => $sort]) }}" class="flex items-center justify-between rounded px-3 py-2.5 {{ $selectedCategory === $category->slug ? 'bg-orange-50 text-orange-700' : 'hover:bg-slate-50 hover:text-orange-700' }}">
                        <span>{{ $category->name }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div id="product-results" aria-live="polite" aria-busy="false">
            <div class="mb-4 flex flex-col gap-3 rounded-md border border-[#dbe8f3] bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-bold text-slate-600">
                    Browse our school supplies
                    @if ($search)
                        for <span class="text-[#07215f]">{{ $search }}</span>
                    @endif
                </p>
                <div class="no-scrollbar flex gap-2 overflow-x-auto text-xs font-black">
                    @foreach ($categories->take(6) as $category)
                        <a href="{{ route('website.products.index', ['category' => $category->slug]) }}" class="whitespace-nowrap rounded-full border border-[#d7e4ef] px-3 py-1.5 text-[#173267] hover:border-orange-500 hover:text-orange-700">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
                @forelse ($products as $product)
                    <article class="group overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <a href="{{ route('website.products.show', $product) }}" class="relative block aspect-[4/3] bg-slate-100">
                            <span class="absolute left-2 top-2 z-10 rounded bg-white/95 px-2 py-1 text-[10px] font-black uppercase text-orange-700 shadow-sm">{{ $product->category?->name ?? 'Supply' }}</span>
                            <x-ui.responsive-image
                                :src="$product->image_url"
                                :alt="$product->name"
                                :label="$product->category?->name ?? 'EduKit Supply'"
                                fallback-class="grid h-full w-full place-items-center bg-orange-50 px-3 text-center text-xs font-black uppercase tracking-wide text-orange-800"
                            />
                        </a>
                        <div class="p-3">
                            <a href="{{ route('website.products.show', $product) }}" class="line-clamp-2 block min-h-10 text-[12px] font-extrabold leading-5 text-[#07215f] group-hover:text-orange-700 sm:text-[14px]">{{ $product->name }}</a>
                            @if ($product->brand || $product->color)
                                <p class="mt-1 truncate text-[10px] font-semibold text-slate-500 sm:text-[11px]">{{ collect([$product->brand, $product->color])->filter()->join(' · ') }}</p>
                            @endif
                            <p class="mt-2 text-[14px] font-black text-slate-950 sm:mt-3 sm:text-[16px]">UGX {{ number_format($product->marketplace_price) }}</p>
                            <div class="mt-4">
                                @if ($product->marketplace_stock_quantity > 0)
                                    <form method="POST" action="{{ route('website.cart.store', $product) }}" class="grid grid-cols-[3.5rem_minmax(0,1fr)_minmax(0,1fr)] gap-1.5 min-[420px]:grid-cols-2 min-[420px]:gap-2">
                                        @csrf
                                        <label class="sr-only" for="product-quantity-{{ $product->id }}">Quantity</label>
                                        <input id="product-quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ $product->marketplace_stock_quantity }}" value="1" class="h-9 w-full rounded-md border-[#d7e4ef] text-center text-xs font-bold text-[#07215f] focus:border-orange-600 focus:ring-orange-600 min-[420px]:col-span-2">
                                        <button class="min-w-0 rounded-md bg-orange-600 px-1 py-2 text-[10px] font-extrabold text-white hover:bg-orange-700 sm:px-2 sm:text-xs" aria-label="Add {{ $product->name }} to cart"><span class="sm:hidden">Add</span><span class="hidden sm:inline">Add to cart</span></button>
                                        <button name="checkout" value="1" class="min-w-0 rounded-md bg-[#07215f] px-1 py-2 text-[10px] font-extrabold text-white hover:bg-orange-700 sm:px-2 sm:text-xs" aria-label="Buy {{ $product->name }} now"><span class="sm:hidden">Buy now</span><span class="hidden sm:inline">Checkout</span></button>
                                    </form>
                                @else
                                    <span class="grid min-h-10 place-items-center rounded-md bg-slate-100 px-3 py-2 text-xs font-extrabold text-slate-500">Out of stock</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-2 border border-dashed border-slate-300 bg-white p-8 xl:col-span-4">
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

@push('scripts')
<script>
(() => {
    const form = document.getElementById('product-filter-form');
    const results = document.getElementById('product-results');
    if (!form || !results || !window.fetch) return;
    let timer;
    let controller;
    const load = (url) => {
        controller?.abort();
        controller = new AbortController();
        results.setAttribute('aria-busy', 'true');
        fetch(url, { signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => { if (!response.ok) throw new Error('Search failed'); return response.text(); })
            .then(html => {
                const page = new DOMParser().parseFromString(html, 'text/html');
                const next = page.getElementById('product-results');
                if (!next) throw new Error('Search results missing');
                results.innerHTML = next.innerHTML;
                history.replaceState({}, '', url);
            })
            .catch(error => { if (error.name !== 'AbortError') form.submit(); })
            .finally(() => results.setAttribute('aria-busy', 'false'));
    };
    const search = () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const url = new URL(form.action);
            new FormData(form).forEach((value, key) => { if (value) url.searchParams.set(key, value); });
            load(url);
        }, 250);
    };
    form.addEventListener('input', search);
    form.addEventListener('change', search);
    form.addEventListener('submit', event => { event.preventDefault(); clearTimeout(timer); search(); });
    results.addEventListener('click', event => {
        const link = event.target.closest('a[href*="page="]');
        if (!link || !results.contains(link)) return;
        event.preventDefault();
        load(link.href);
    });
})();
</script>
@endpush
