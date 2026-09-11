@extends('website.layout')

@section('title', 'EduKit - School Supply Marketplace')

@section('content')
    @php
        $heroSlides = [
            [
                'eyebrow' => "Uganda's school supply platform",
                'title' => 'Everything for their education, delivered with care.',
                'accent' => 'delivered with care.',
                'copy' => 'Buy school supplies, uniforms, books and more from trusted suppliers. We source, package and deliver to schools, homes or pickup points.',
                'image' => '/images/website/edukit-hero.png',
                'primary' => ['label' => 'Shop Now', 'route' => route('website.products.index')],
                'secondary' => ['label' => 'Upload Shopping List', 'route' => route('website.upload-list')],
            ],
            [
                'eyebrow' => 'Term-ready bundles',
                'title' => 'Books, stationery and bags prepared from one list.',
                'accent' => 'one list.',
                'copy' => 'Send your school list and EduKit turns it into a reviewed basket using the live product catalogue.',
                'image' => '/images/products/hero1.jpeg',
                'primary' => ['label' => 'Upload List', 'route' => route('website.upload-list')],
                'secondary' => ['label' => 'Browse Products', 'route' => route('website.products.index')],
            ],
            [
                'eyebrow' => 'Verified suppliers',
                'title' => 'Suppliers, schools and delivery teams working together.',
                'accent' => 'working together.',
                'copy' => 'EduKit helps connect approved suppliers with school supply demand and fulfilment workflows across Uganda.',
                'image' => '/images/products/school_equipment.jpeg',
                'primary' => ['label' => 'Become a Supplier', 'route' => route('website.suppliers')],
                'secondary' => ['label' => 'How It Works', 'route' => '#how-it-works'],
            ],
        ];

        $categoryArtwork = [
            'School Uniforms' => '/images/products/school_uniform.jpeg',
            'Books' => '/images/products/exercise-books.jpg',
            'Stationery' => '/images/products/school_equipment.jpeg',
            'Shoes' => '/images/products/black-school-shoes.svg',
            'Bags' => '/images/products/school-backpack.jpg',
            'Bedding and Linen' => '/images/products/school_material.jpeg',
            'Toiletries' => '/images/products/dove-deodorant.png',
            'School Equipment' => '/images/products/school_equipment.jpeg',
            'Other Supplies' => '/images/products/shoopinggcart.jpeg',
        ];

        $featuredTabs = $featuredProducts->pluck('category.name')->filter()->unique()->take(5);
    @endphp

    <section class="relative overflow-hidden border-b border-[#dbe8f3] bg-[#eaf7ff]" data-hero-slider>
        <div class="relative min-h-[520px] sm:min-h-[560px] lg:min-h-[590px]">
            @foreach ($heroSlides as $index => $slide)
                <article class="hero-slide {{ $index === 0 ? 'is-active' : '' }} absolute inset-0" data-hero-slide>
                    <div class="absolute inset-0 bg-gradient-to-r from-white via-white/95 to-white/45"></div>
                    <div class="absolute inset-y-0 right-0 hidden w-[58%] sm:block">
                        <img src="{{ $slide['image'] }}" alt="" class="h-full w-full object-contain object-right-bottom p-5 lg:p-10">
                    </div>
                    <div class="relative mx-auto grid min-h-[520px] max-w-7xl content-center px-4 py-10 sm:min-h-[560px] sm:px-6 lg:min-h-[590px] lg:px-8">
                        <div class="max-w-[620px]">
                            <p class="inline-flex rounded-md border border-emerald-200 bg-white/90 px-3 py-1.5 text-[10px] font-black uppercase tracking-wide text-emerald-700 shadow-sm sm:text-[11px]">
                                {{ $slide['eyebrow'] }}
                            </p>
                            <h1 class="mt-4 max-w-[640px] text-[34px] font-black leading-[1.05] text-[#07215f] min-[420px]:text-[42px] sm:text-[58px]">
                                {{ \Illuminate\Support\Str::before($slide['title'], $slide['accent']) }}<span class="text-emerald-600">{{ $slide['accent'] }}</span>
                            </h1>
                            <p class="mt-5 max-w-xl text-sm font-semibold leading-7 text-slate-600 sm:text-base">
                                {{ $slide['copy'] }}
                            </p>
                            <div class="mt-7 flex flex-wrap gap-3">
                                <a href="{{ $slide['primary']['route'] }}" class="rounded-md bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-emerald-700">
                                    {{ $slide['primary']['label'] }}
                                </a>
                                <a href="{{ $slide['secondary']['route'] }}" class="rounded-md border border-[#d7e4ef] bg-white px-5 py-3 text-sm font-black text-[#07215f] shadow-sm hover:border-emerald-500 hover:text-emerald-700">
                                    {{ $slide['secondary']['label'] }}
                                </a>
                            </div>

                            <div class="mt-8 grid max-w-xl grid-cols-2 gap-3 text-[11px] font-extrabold text-[#19366f] sm:grid-cols-4 sm:text-[12px]">
                                <div class="rounded-md bg-white/80 p-3 shadow-sm">Delivery to schools and homes</div>
                                <div class="rounded-md bg-white/80 p-3 shadow-sm">Secure checkout flow</div>
                                <div class="rounded-md bg-white/80 p-3 shadow-sm">Trusted suppliers</div>
                                <div class="rounded-md bg-white/80 p-3 shadow-sm">Supporting learners</div>
                            </div>
                        </div>

                        <div class="mt-8 block sm:hidden">
                            <img src="{{ $slide['image'] }}" alt="" class="mx-auto max-h-56 w-full object-contain">
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="absolute bottom-5 left-0 right-0">
            <div class="mx-auto flex max-w-7xl items-center gap-2 px-4 sm:px-6 lg:px-8">
                @foreach ($heroSlides as $index => $slide)
                    <button type="button" class="hero-dot {{ $index === 0 ? 'is-active' : '' }} h-2.5 w-8 rounded-full bg-[#07215f]/25 transition hover:bg-emerald-500" data-hero-dot="{{ $index }}" aria-label="Show slide {{ $index + 1 }}"></button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-9 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-[24px] font-black text-[#07215f] sm:text-[28px]">Shop by category</h2>
                    <p class="mt-1 text-sm font-medium text-slate-600">Start with the main school supply groups.</p>
                </div>
                <a href="{{ route('website.products.index') }}" class="hidden text-sm font-black text-emerald-700 hover:text-emerald-900 sm:block">View all categories</a>
            </div>

            <div class="no-scrollbar mt-6 grid auto-cols-[132px] grid-flow-col gap-3 overflow-x-auto pb-2 sm:auto-cols-fr sm:grid-flow-row sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-9">
                @foreach ($categories as $category)
                    @php($image = $categoryArtwork[$category->name] ?? $categoryImages->get($category->id))
                    <a href="{{ route('website.products.index', ['category' => $category->slug]) }}" class="group rounded-md border border-[#dbe8f3] bg-white p-3 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-500 hover:shadow-md">
                        <div class="mx-auto grid aspect-square w-20 place-items-center overflow-hidden rounded-md bg-[#f1f8ff]">
                            @if ($image)
                                <img src="{{ $image }}" alt="{{ $category->name }}" class="h-full w-full object-contain p-2">
                            @else
                                <span class="text-sm font-black text-emerald-700">EK</span>
                            @endif
                        </div>
                        <p class="mt-3 min-h-9 text-[11px] font-black leading-4 text-[#07215f] group-hover:text-emerald-700">{{ $category->name }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-4 px-4 pb-10 sm:px-6 md:grid-cols-3 lg:px-8">
        <a href="{{ route('website.upload-list') }}" class="group grid min-h-[150px] grid-cols-[1fr_118px] overflow-hidden rounded-md border border-emerald-100 bg-emerald-50 shadow-sm transition hover:border-emerald-400">
            <div class="p-5">
                <p class="text-[21px] font-black leading-7 text-[#07215f]">Upload your school list</p>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-700">Take a photo or upload a file. We prepare a quote.</p>
                <span class="mt-4 inline-flex rounded-md bg-emerald-600 px-4 py-2 text-xs font-black text-white group-hover:bg-emerald-700">Upload List</span>
            </div>
            <img src="/images/products/shoopinggcart.jpeg" alt="" class="h-full w-full object-contain p-3">
        </a>

        <a href="{{ route('website.products.index') }}" class="group grid min-h-[150px] grid-cols-[1fr_118px] overflow-hidden rounded-md border border-sky-100 bg-sky-50 shadow-sm transition hover:border-sky-400">
            <div class="p-5">
                <p class="text-[21px] font-black leading-7 text-[#07215f]">Deliver to school or home</p>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-700">Choose delivery to school, home or pickup point.</p>
                <span class="mt-4 inline-flex rounded-md bg-[#1674d1] px-4 py-2 text-xs font-black text-white group-hover:bg-[#07215f]">Learn More</span>
            </div>
            <img src="/images/products/school_equipment.jpeg" alt="" class="h-full w-full object-cover">
        </a>

        <a href="{{ route('website.suppliers') }}" class="group grid min-h-[150px] grid-cols-[1fr_118px] overflow-hidden rounded-md border border-amber-100 bg-amber-50 shadow-sm transition hover:border-amber-400">
            <div class="p-5">
                <p class="text-[21px] font-black leading-7 text-[#07215f]">Trusted suppliers</p>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-700">Verified suppliers across Uganda can join EduKit.</p>
                <span class="mt-4 inline-flex rounded-md bg-emerald-600 px-4 py-2 text-xs font-black text-white group-hover:bg-emerald-700">Become a Supplier</span>
            </div>
            <img src="/images/products/school_material.jpeg" alt="" class="h-full w-full object-cover">
        </a>
    </section>

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
                            <p class="mt-1 text-[11px] font-semibold text-amber-500">★★★★★ <span class="text-slate-400">({{ max(12, $product->stock_quantity) }})</span></p>
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

    @if ($spotlightProduct)
        <section class="bg-[#07215f]">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-[360px_1fr] lg:px-8">
                <div class="text-white">
                    <p class="text-[11px] font-black uppercase tracking-wide text-emerald-300">Product stage</p>
                    <h2 class="mt-2 text-[28px] font-black leading-tight">Built from the admin catalogue</h2>
                    <p class="mt-3 text-sm font-medium leading-7 text-blue-100">Products added or edited in the admin dashboard flow directly into the public shop.</p>

                    <article class="mt-6 rounded-md bg-white p-4 text-[#07215f] shadow-lg">
                        <a href="{{ route('website.products.show', $spotlightProduct) }}" class="block aspect-[4/3] overflow-hidden rounded-md bg-sky-50">
                            @if ($spotlightProduct->image_url)
                                <img src="{{ $spotlightProduct->image_url }}" alt="{{ $spotlightProduct->name }}" class="h-full w-full object-contain p-4">
                            @endif
                        </a>
                        <p class="mt-4 text-[11px] font-black uppercase text-slate-500">{{ $spotlightProduct->category?->name ?? 'School supply' }}</p>
                        <a href="{{ route('website.products.show', $spotlightProduct) }}" class="mt-1 block text-xl font-black">{{ $spotlightProduct->name }}</a>
                        <p class="mt-2 text-lg font-black text-emerald-700">UGX {{ number_format($spotlightProduct->price) }}</p>
                    </article>
                </div>

                <div class="grid grid-cols-2 content-start gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($stageProducts->take(8) as $product)
                        <article class="rounded-md border border-white/10 bg-white p-3 shadow-sm">
                            <a href="{{ route('website.products.show', $product) }}" class="block aspect-square overflow-hidden rounded-md bg-sky-50">
                                @if ($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-2">
                                @endif
                            </a>
                            <p class="mt-3 min-h-10 text-[12px] font-black leading-5 text-[#07215f]">{{ $product->name }}</p>
                            <p class="mt-1 text-[12px] font-black text-emerald-700">UGX {{ number_format($product->price) }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section id="how-it-works" class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <h2 class="text-[24px] font-black text-[#07215f] sm:text-[28px]">How EduKit works</h2>
            <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['1', 'Shop or upload list', 'Browse products or upload a school list.'],
                    ['2', 'Checkout and pay', 'Select delivery option and pay securely.'],
                    ['3', 'We fulfil and deliver', 'We source from trusted suppliers and deliver.'],
                    ['4', 'Receive and confirm', 'Your school, home or pickup point confirms receipt.'],
                ] as [$step, $title, $copy])
                    <div class="text-center">
                        <span class="mx-auto grid size-11 place-items-center rounded-full bg-emerald-600 text-sm font-black text-white">{{ $step }}</span>
                        <p class="mt-4 font-black text-[#07215f]">{{ $title }}</p>
                        <p class="mx-auto mt-2 max-w-[220px] text-sm leading-6 text-slate-600">{{ $copy }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="text-[24px] font-black text-[#07215f] sm:text-[28px]">Why choose EduKit?</h2>
        <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            @foreach (['Wide range of school products', 'Verified Ugandan suppliers', 'Convenient delivery options', 'Secure payment foundation', 'Trusted by parents and schools', 'Better education for brighter futures'] as $reason)
                <div class="rounded-md border border-[#dbe8f3] bg-white p-4 text-center text-[13px] font-black leading-6 text-[#07215f] shadow-sm">{{ $reason }}</div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-[24px] font-black text-[#07215f] sm:text-[28px]">What parents say</h2>
            <a href="{{ route('website.help') }}" class="text-sm font-black text-emerald-700 hover:text-emerald-900">View all reviews</a>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach ([
                ['Sarah N.', 'Parent, Kampala', 'EduKit made it so easy to buy my daughter&apos;s school items. They delivered everything to her school on time.'],
                ['James K.', 'Parent, Entebbe', 'I love the shopping list feature. I uploaded the list and they handled the rest. Excellent service.'],
                ['Diana M.', 'Parent, Mukono', 'Reliable, fast and affordable. My son received all his items at school without stress.'],
            ] as [$name, $role, $quote])
                <article class="rounded-md border border-[#dbe8f3] bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold leading-6 text-slate-600">{!! $quote !!}</p>
                    <p class="mt-3 text-sm font-black text-amber-500">★★★★★</p>
                    <p class="mt-2 text-sm font-black text-[#07215f]">{{ $name }}</p>
                    <p class="text-xs font-semibold text-slate-500">{{ $role }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <div class="grid overflow-hidden rounded-md bg-emerald-50 md:grid-cols-[210px_1fr]">
            <img src="/images/products/school_uniform.jpeg" alt="" class="h-44 w-full object-cover md:h-full">
            <div class="grid gap-5 p-6 md:grid-cols-[1fr_360px] md:items-center">
                <div>
                    <p class="text-2xl font-black leading-tight text-[#07215f]">Join parents already using EduKit.</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700">Get the latest offers, new products and school tips.</p>
                </div>
                <form class="grid gap-2 sm:flex">
                    <input type="email" placeholder="Enter your email address" class="min-w-0 flex-1 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <button class="rounded bg-emerald-600 px-5 py-2.5 text-sm font-black text-white hover:bg-emerald-700">Subscribe</button>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-hero-slider]').forEach((slider) => {
            const slides = Array.from(slider.querySelectorAll('[data-hero-slide]'));
            const dots = Array.from(slider.querySelectorAll('[data-hero-dot]'));
            let current = 0;

            const show = (index) => {
                current = (index + slides.length) % slides.length;

                slides.forEach((slide, slideIndex) => {
                    slide.classList.toggle('is-active', slideIndex === current);
                });

                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('is-active', dotIndex === current);
                });
            };

            dots.forEach((dot) => {
                dot.addEventListener('click', () => show(Number(dot.dataset.heroDot)));
            });

            window.setInterval(() => show(current + 1), 6500);
        });
    </script>
@endpush
