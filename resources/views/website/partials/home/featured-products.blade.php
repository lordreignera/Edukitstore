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

        <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-6">
            @foreach ($featuredProducts->take(6) as $product)
                <article class="group overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <a href="{{ route('website.products.show', $product) }}" class="block aspect-[1.05] bg-[#f8fbff]">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-3">
                        @else
                            <span class="grid h-full place-items-center text-sm font-bold text-emerald-800">EduKit Supply</span>
                        @endif
                    </a>
                    <div class="p-3">
                        <a href="{{ route('website.products.show', $product) }}" class="block min-h-10 text-[12px] font-black leading-5 text-[#07215f] group-hover:text-emerald-700 sm:text-[13px]">{{ $product->name }}</a>
                        <p class="mt-2 text-[13px] font-black text-slate-950">UGX {{ number_format($product->price) }}</p>
                        <p class="mt-1 text-[11px] font-semibold text-amber-500">5.0 rating <span class="text-slate-400">({{ max(12, $product->stock_quantity) }})</span></p>
                        <form method="POST" action="{{ route('website.cart.store', $product) }}" class="mt-3">
                            @csrf
                            <button class="w-full rounded-md border border-[#d7e4ef] px-3 py-2 text-[11px] font-black text-[#07215f] hover:border-emerald-500 hover:text-emerald-700">Add to Cart</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
