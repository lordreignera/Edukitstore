<section class="border-b border-[#dbe8f3] bg-[#eef6fb]">
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-5 sm:px-6 lg:grid-cols-[236px_minmax(0,1fr)_250px] lg:px-8">
        <aside class="hidden overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm lg:block">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-black text-[#07215f]">Shop categories</p>
            </div>
            <nav class="grid p-2 text-[13px] font-bold text-[#173267]" aria-label="Shop categories">
                @foreach ($categories->take(9) as $category)
                    <a href="{{ route('website.products.index', ['category' => $category->slug]) }}" class="flex items-center justify-between rounded px-3 py-2.5 hover:bg-emerald-50 hover:text-emerald-700">
                        <span>{{ $category->name }}</span>
                        <span class="text-[11px] text-slate-400">{{ $category->products_count }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <section class="relative overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm" data-hero-slider>
            <div class="relative min-h-[310px] sm:min-h-[340px]">
                @foreach ($heroSlides as $index => $slide)
                    <article class="hero-slide {{ $index === 0 ? 'is-active' : '' }} absolute inset-0" data-hero-slide>
                        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $slide['image'] }}')"></div>
                        <div class="absolute inset-0 bg-gradient-to-r from-white via-white/90 to-white/20"></div>
                        <div class="relative grid min-h-[310px] content-center px-5 py-8 sm:min-h-[340px] sm:px-8">
                            <div class="max-w-[520px]">
                                <p class="inline-flex rounded border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase text-emerald-700">{{ $slide['eyebrow'] }}</p>
                                <h1 class="mt-3 text-[28px] font-black leading-tight text-[#07215f] sm:text-[42px]">
                                    {{ \Illuminate\Support\Str::before($slide['title'], $slide['accent']) }}<span class="text-emerald-600">{{ $slide['accent'] }}</span>
                                </h1>
                                <p class="mt-3 max-w-md text-sm font-semibold leading-6 text-slate-600">{{ $slide['copy'] }}</p>
                                <div class="mt-5 flex flex-wrap gap-2">
                                    <a href="{{ $slide['primary']['route'] }}" class="rounded-md bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700">{{ $slide['primary']['label'] }}</a>
                                    <a href="{{ $slide['secondary']['route'] }}" class="rounded-md border border-[#d7e4ef] bg-white px-4 py-2.5 text-xs font-black text-[#07215f] shadow-sm hover:border-emerald-500">{{ $slide['secondary']['label'] }}</a>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="absolute bottom-4 left-5 flex items-center gap-2 sm:left-8">
                @foreach ($heroSlides as $index => $slide)
                    <button type="button" class="hero-dot {{ $index === 0 ? 'is-active' : '' }} h-2 w-7 rounded-full bg-[#07215f]/25 transition hover:bg-emerald-500" data-hero-dot="{{ $index }}" aria-label="Show slide {{ $index + 1 }}"></button>
                @endforeach
            </div>
        </section>

        <aside class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
            <a href="{{ route('website.upload-list') }}" class="rounded-md border border-emerald-100 bg-white p-4 shadow-sm hover:border-emerald-400">
                <p class="text-sm font-black text-[#07215f]">Upload school list</p>
                <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">Send a list and get an invoice with delivery fee.</p>
                <span class="mt-3 inline-flex text-xs font-black text-emerald-700">Start request</span>
            </a>
            <a href="{{ route('website.track-order') }}" class="rounded-md border border-blue-100 bg-white p-4 shadow-sm hover:border-blue-400">
                <p class="text-sm font-black text-[#07215f]">Track invoice</p>
                <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">Check quotation, payment and delivery status.</p>
                <span class="mt-3 inline-flex text-xs font-black text-blue-700">Track order</span>
            </a>
            <a href="{{ route('website.products.index') }}" class="overflow-hidden rounded-md border border-amber-100 bg-white shadow-sm hover:border-amber-400">
                <div class="h-24 bg-cover bg-center" style="background-image: url('/images/products/all.jpeg')"></div>
                <div class="p-4">
                    <p class="text-sm font-black text-[#07215f]">Term supplies</p>
                    <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">Books, stationery, bags and toiletries.</p>
                </div>
            </a>
        </aside>
    </div>
</section>
