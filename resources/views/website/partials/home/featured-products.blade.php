@if ($featuredProducts->isNotEmpty())
    <section id="shop" class="border-b border-[#dbe8f3] bg-white">
        <div class="mx-auto max-w-7xl px-4 pb-8 pt-5 sm:px-6 lg:px-8">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-orange-700">Shop EduKit</p>
                <h1 class="mt-1 text-[25px] font-black leading-tight text-[#07215f] sm:text-[30px]">School essentials, ready to order</h1>
                <p class="mt-1 text-sm font-medium text-slate-600">Browse books, pens, uniforms, bags and more from the EduKit catalogue.</p>
            </div>
            <a href="{{ route('website.products.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-md bg-orange-600 px-4 py-2 text-xs font-black text-white shadow-sm hover:bg-orange-700">View all products</a>
            </div>

            <div class="no-scrollbar mt-4 flex gap-2 overflow-x-auto pb-1 text-[11px] font-black text-slate-600">
                <a href="{{ route('website.products.index') }}" class="whitespace-nowrap rounded-full border border-orange-500 bg-orange-50 px-3 py-1.5 text-orange-700">All products</a>
                @foreach ($featuredTabs as $tab)
                    <a href="{{ route('website.products.index', ['category' => \Illuminate\Support\Str::slug($tab)]) }}" class="whitespace-nowrap rounded-full border border-[#d7e4ef] bg-white px-3 py-1.5 hover:border-orange-500 hover:text-orange-700">{{ $tab }}</a>
                @endforeach
            </div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ($featuredProducts->take(6) as $product)
                <article class="group overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <a href="{{ route('website.products.show', $product) }}" class="relative block aspect-[4/3] bg-[#f8fbff] sm:aspect-square">
                        <span class="absolute left-2 top-2 z-10 rounded bg-white/95 px-2 py-1 text-[10px] font-black uppercase text-orange-700 shadow-sm">{{ $product->category?->name ?? 'Supply' }}</span>
                        <x-ui.responsive-image
                            :src="$product->image_url"
                            :alt="$product->name"
                            :label="$product->category?->name ?? 'EduKit Supply'"
                            fallback-class="grid h-full w-full place-items-center px-3 text-center text-xs font-black uppercase tracking-wide text-orange-800"
                        />
                    </a>
                    <div class="p-3">
                        <a href="{{ route('website.products.show', $product) }}" class="line-clamp-2 block min-h-10 text-[12px] font-black leading-5 text-[#07215f] group-hover:text-orange-700 sm:text-[13px]">{{ $product->name }}</a>
                        <p class="mt-2 text-[15px] font-black text-slate-950">UGX {{ number_format($product->marketplace_price) }}</p>
                        @if ($product->marketplace_stock_quantity > 0)
                            <form method="POST" action="{{ route('website.cart.store', $product) }}" class="mt-3 grid grid-cols-2 gap-2">
                                @csrf
                                <label class="sr-only" for="featured-quantity-{{ $product->id }}">Quantity</label>
                                <input id="featured-quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ $product->marketplace_stock_quantity }}" value="1" class="col-span-2 h-11 w-full rounded-md border-[#d7e4ef] text-center text-sm font-bold text-[#07215f] focus:border-orange-600 focus:ring-orange-600">
                                <button class="min-h-11 min-w-0 rounded-md bg-orange-600 px-2 py-2 text-[11px] font-black text-white hover:bg-orange-700 sm:text-xs" aria-label="Add {{ $product->name }} to cart"><span class="sm:hidden">Add</span><span class="hidden sm:inline">Add to cart</span></button>
                                <button name="checkout" value="1" class="min-h-11 min-w-0 rounded-md bg-[#07215f] px-2 py-2 text-[11px] font-black text-white hover:bg-orange-700 sm:text-xs" aria-label="Buy {{ $product->name }} now"><span class="sm:hidden">Buy now</span><span class="hidden sm:inline">Checkout</span></button>
                            </form>
                        @else
                            <span class="mt-3 grid h-9 place-items-center rounded-md bg-slate-100 px-3 py-2 text-[11px] font-black text-slate-500">Out of stock</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-5 text-center">
            <a href="{{ route('website.products.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-[#d7e4ef] bg-white px-5 py-2 text-sm font-black text-[#07215f] hover:border-orange-500 hover:text-orange-700">Browse the full catalogue</a>
        </div>
        </div>
    </section>
@endif
