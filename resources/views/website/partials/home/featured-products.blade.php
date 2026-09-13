@if ($featuredProducts->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-[24px] font-black text-[#07215f] sm:text-[28px]">Featured products</h2>
                <p class="mt-1 text-sm font-medium text-slate-600">Real school supplies from the live EduKit catalogue.</p>
            </div>
            <div class="no-scrollbar flex gap-5 overflow-x-auto text-[12px] font-black text-slate-500">
                <a href="{{ route('website.products.index') }}" class="border-b-2 border-emerald-500 pb-2 text-emerald-700">All</a>
                @foreach ($featuredTabs as $tab)
                    <a href="{{ route('website.products.index', ['category' => \Illuminate\Support\Str::slug($tab)]) }}" class="whitespace-nowrap border-b-2 border-transparent pb-2 hover:text-emerald-700">{{ $tab }}</a>
                @endforeach
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-3 min-[420px]:grid-cols-2 sm:gap-4 md:grid-cols-3 xl:grid-cols-6">
            @foreach ($featuredProducts->take(6) as $product)
                <article class="group overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <a href="{{ route('website.products.show', $product) }}" class="block aspect-[1.05] bg-[#f8fbff]">
                        <x-ui.responsive-image
                            :src="$product->image_url"
                            :alt="$product->name"
                            :label="$product->category?->name ?? 'EduKit Supply'"
                            fallback-class="grid h-full w-full place-items-center px-3 text-center text-xs font-black uppercase tracking-wide text-emerald-800"
                        />
                    </a>
                    <div class="p-3">
                        <a href="{{ route('website.products.show', $product) }}" class="block min-h-10 text-[12px] font-black leading-5 text-[#07215f] group-hover:text-emerald-700 sm:text-[13px]">{{ $product->name }}</a>
                        <p class="mt-2 text-[13px] font-black text-slate-950">UGX {{ number_format($product->price) }}</p>
                        <form method="POST" action="{{ route('website.cart.store', $product) }}" class="mt-3">
                            @csrf
                            <label class="sr-only" for="featured-quantity-{{ $product->id }}">Quantity</label>
                            <input id="featured-quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ $product->stock_quantity }}" value="1" class="mb-2 h-9 w-full rounded-md border-[#d7e4ef] text-center text-xs font-bold text-[#07215f] focus:border-emerald-600 focus:ring-emerald-600">
                            <button class="w-full rounded-md border border-[#d7e4ef] px-3 py-2 text-[11px] font-black text-[#07215f] hover:border-emerald-500 hover:text-emerald-700">Add to Cart</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
