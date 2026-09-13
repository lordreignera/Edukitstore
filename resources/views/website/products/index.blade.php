@extends('website.layout')

@section('title', 'Products - EduKit')

@section('content')
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-[11px] font-extrabold uppercase text-emerald-700">Products</p>
            <h1 class="mt-2 text-[30px] font-extrabold leading-tight text-[#07215f] sm:text-[36px]">School supplies catalogue</h1>

            <form method="GET" action="{{ route('website.products.index') }}" class="mt-8 grid gap-3 rounded-md border border-[#dbe8f3] bg-[#f7fbff] p-4 shadow-sm md:grid-cols-[1fr_220px_auto_auto]">
                <input name="search" value="{{ $search }}" placeholder="Search products, code or brand" class="rounded-md border-[#d7e4ef] text-sm font-medium focus:border-emerald-600 focus:ring-emerald-600">
                <select name="category" class="rounded-md border-[#d7e4ef] text-sm font-medium focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected($selectedCategory === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-[#07215f] px-5 py-2 text-sm font-extrabold text-white hover:bg-emerald-700">Filter</button>
                <a href="{{ route('website.products.index') }}" class="grid rounded-md border border-[#d7e4ef] bg-white px-5 py-2 text-sm font-extrabold text-[#07215f] hover:border-emerald-500">Clear</a>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-3 min-[420px]:grid-cols-2 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
            @forelse ($products as $product)
                <article class="group overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <a href="{{ route('website.products.show', $product) }}" class="block aspect-[4/3] bg-slate-100">
                        <x-ui.responsive-image
                            :src="$product->image_url"
                            :alt="$product->name"
                            :label="$product->category?->name ?? 'EduKit Supply'"
                            fallback-class="grid h-full w-full place-items-center bg-emerald-50 px-3 text-center text-xs font-black uppercase tracking-wide text-emerald-800"
                        />
                    </a>
                    <div class="p-3 sm:p-4">
                        <p class="text-[10px] font-extrabold uppercase text-slate-500 sm:text-[11px]">{{ $product->category?->name ?? 'School supply' }}</p>
                        <a href="{{ route('website.products.show', $product) }}" class="mt-2 block min-h-10 text-[14px] font-extrabold leading-5 text-[#07215f] group-hover:text-emerald-700">{{ $product->name }}</a>
                        <p class="mt-3 text-[15px] font-extrabold text-slate-950">UGX {{ number_format($product->price) }}</p>
                        <p class="mt-1 text-[11px] text-slate-500 sm:text-xs">{{ $product->stock_quantity }} available</p>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <a href="{{ route('website.products.show', $product) }}" class="rounded-md border border-[#d7e4ef] px-3 py-2 text-center text-xs font-extrabold text-[#07215f] hover:border-emerald-500">View</a>
                            <form method="POST" action="{{ route('website.cart.store', $product) }}">
                                @csrf
                                <label class="sr-only" for="product-quantity-{{ $product->id }}">Quantity</label>
                                <input id="product-quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ $product->stock_quantity }}" value="1" class="mb-2 h-9 w-full rounded-md border-[#d7e4ef] text-center text-xs font-bold text-[#07215f] focus:border-emerald-600 focus:ring-emerald-600">
                                <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-xs font-extrabold text-white hover:bg-emerald-700">Add</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="border border-dashed border-slate-300 bg-white p-8 min-[420px]:col-span-2 lg:col-span-4">
                    <p class="font-semibold text-slate-950">No products match this search.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    </section>
@endsection
