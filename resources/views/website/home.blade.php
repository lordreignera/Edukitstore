@extends('website.layout')

@section('title', 'EduKit - School Supply Marketplace')

@section('content')
    <section class="relative overflow-hidden bg-[#eaf7ff]">
        <div class="absolute inset-0 bg-cover bg-[73%_center] sm:bg-center" style="background-image: url('/images/website/edukit-hero.png')"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-white via-white/95 to-white/40"></div>
        <div class="relative mx-auto grid min-h-[460px] max-w-7xl content-center px-4 py-10 sm:min-h-[500px] sm:px-6 lg:px-8">
            <div class="max-w-[660px]">
                <p class="inline-flex rounded-md border border-emerald-200 bg-white/90 px-3 py-1.5 text-[11px] font-extrabold uppercase text-emerald-700 shadow-sm">Uganda's school supply platform</p>
                <h1 class="mt-5 text-[34px] font-extrabold leading-[1.08] text-[#07215f] min-[420px]:text-[40px] sm:text-[60px]">
                    Everything for their education, <span class="text-emerald-600">delivered with care.</span>
                </h1>
                <p class="mt-5 max-w-xl text-[15px] font-semibold leading-7 text-slate-600 sm:text-base">
                    Buy school supplies, uniforms, books and more from trusted suppliers. We source, package and deliver to schools, homes or anywhere in Uganda.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('website.products.index') }}" class="rounded-md bg-emerald-600 px-6 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-emerald-700">Shop Now</a>
                    <a href="{{ route('website.upload-list') }}" class="rounded-md border border-[#d7e4ef] bg-white px-6 py-3 text-sm font-extrabold text-[#07215f] shadow-sm hover:border-emerald-500">Upload Shopping List</a>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-3 px-4 py-5 text-[12px] font-extrabold text-[#19366f] sm:grid-cols-4 sm:px-6 sm:text-[13px] lg:px-8">
            <div class="rounded-md bg-sky-50 p-3">Delivery to schools and homes</div>
            <div class="rounded-md bg-emerald-50 p-3">Secure payments</div>
            <div class="rounded-md bg-amber-50 p-3">Trusted suppliers</div>
            <div class="rounded-md bg-rose-50 p-3">Supporting Uganda's education</div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-[28px] font-extrabold text-[#07215f]">Shop by category</h2>
                <p class="mt-2 text-sm text-slate-600">Browse EduKit's curated school supply catalogue.</p>
            </div>
            <a href="{{ route('website.products.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">View all categories</a>
        </div>
        <div class="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($categories as $category)
                <a href="{{ route('website.products.index', ['category' => $category->slug]) }}" class="group rounded-md border border-[#dbe8f3] bg-white p-4 text-center shadow-sm hover:border-emerald-500 hover:shadow-md">
                    <div class="mx-auto grid aspect-square w-20 place-items-center overflow-hidden rounded-md bg-sky-50">
                        @if ($categoryImages->get($category->id))
                            <img src="{{ $categoryImages->get($category->id) }}" alt="{{ $category->name }}" class="h-full w-full object-contain p-2">
                        @else
                            <span class="text-lg font-black text-emerald-700">EK</span>
                        @endif
                    </div>
                    <p class="mt-3 min-h-10 text-[13px] font-extrabold leading-5 text-[#07215f] group-hover:text-emerald-700">{{ $category->name }}</p>
                    <p class="text-xs text-slate-500">{{ $category->products_count }} products</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-5 px-4 pb-12 sm:px-6 lg:grid-cols-3 lg:px-8">
        <a href="{{ route('website.upload-list') }}" class="rounded-md border border-emerald-100 bg-emerald-50 p-6 shadow-sm hover:border-emerald-400">
            <p class="text-[24px] font-extrabold leading-8 text-[#07215f]">Upload your school list</p>
            <p class="mt-3 text-sm leading-6 text-slate-700">Shopping-list matching will connect parent lists to the master product catalogue.</p>
        </a>
        <a href="{{ route('website.products.index') }}" class="rounded-md border border-sky-100 bg-sky-50 p-6 shadow-sm hover:border-sky-400">
            <p class="text-[24px] font-extrabold leading-8 text-[#07215f]">Deliver to school or home</p>
            <p class="mt-3 text-sm leading-6 text-slate-700">School, home, other address and pickup fulfilment are part of the EduKit flow.</p>
        </a>
        <a href="{{ route('website.suppliers') }}" class="rounded-md border border-amber-100 bg-amber-50 p-6 shadow-sm hover:border-amber-400">
            <p class="text-[24px] font-extrabold leading-8 text-[#07215f]">Trusted suppliers</p>
            <p class="mt-3 text-sm leading-6 text-slate-700">We work with trusted suppliers across Uganda to source school essentials.</p>
        </a>
    </section>

    @if ($spotlightProduct)
        <section class="bg-[#07215f]">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-[420px_1fr] lg:px-8">
                <div class="text-white">
                    <p class="text-[11px] font-extrabold uppercase text-emerald-300">Product stage</p>
                    <h2 class="mt-2 text-[30px] font-extrabold leading-tight sm:text-[36px]">Ready for the school term</h2>
                    <p class="mt-3 text-sm font-medium leading-7 text-blue-100">A quick look at popular supplies from the live EduKit catalogue.</p>

                    <article class="mt-6 rounded-md bg-white p-4 text-[#07215f] shadow-lg">
                        <a href="{{ route('website.products.show', $spotlightProduct) }}" class="block aspect-[4/3] overflow-hidden rounded-md bg-sky-50">
                            @if ($spotlightProduct->image_url)
                                <img src="{{ $spotlightProduct->image_url }}" alt="{{ $spotlightProduct->name }}" class="h-full w-full object-contain p-4">
                            @endif
                        </a>
                        <p class="mt-4 text-[11px] font-extrabold uppercase text-slate-500">{{ $spotlightProduct->category?->name ?? 'School supply' }}</p>
                        <a href="{{ route('website.products.show', $spotlightProduct) }}" class="mt-1 block text-xl font-extrabold">{{ $spotlightProduct->name }}</a>
                        <p class="mt-2 text-lg font-extrabold text-emerald-700">UGX {{ number_format($spotlightProduct->price) }}</p>
                        <form method="POST" action="{{ route('website.cart.store', $spotlightProduct) }}" class="mt-4">
                            @csrf
                            <button class="w-full rounded-md bg-emerald-600 px-4 py-3 text-sm font-extrabold text-white hover:bg-emerald-700">Add to Cart</button>
                        </form>
                    </article>
                </div>

                <div class="grid grid-cols-2 content-start gap-3 sm:grid-cols-3">
                    @foreach ($stageProducts as $product)
                        <article class="rounded-md border border-white/10 bg-white p-3 shadow-sm">
                            <a href="{{ route('website.products.show', $product) }}" class="block aspect-square overflow-hidden rounded-md bg-sky-50">
                                @if ($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-2">
                                @endif
                            </a>
                            <p class="mt-3 min-h-10 text-[13px] font-extrabold leading-5 text-[#07215f]">{{ $product->name }}</p>
                            <p class="mt-1 text-[13px] font-extrabold text-emerald-700">UGX {{ number_format($product->price) }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-[28px] font-extrabold text-[#07215f]">Featured products</h2>
                <p class="mt-2 text-sm text-slate-600">Popular school supplies selected for parents and learners.</p>
            </div>
            <a href="{{ route('website.products.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">View all products</a>
        </div>

        <div class="mt-7 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
            @forelse ($featuredProducts as $product)
                <article class="group overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <a href="{{ route('website.products.show', $product) }}" class="block aspect-[4/3] bg-slate-50">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-3">
                        @else
                            <span class="grid h-full place-items-center bg-emerald-50 text-sm font-bold text-emerald-800">EduKit Supply</span>
                        @endif
                    </a>
                    <div class="p-3 sm:p-4">
                        <p class="text-[10px] font-extrabold uppercase text-slate-500 sm:text-xs">{{ $product->category?->name ?? 'School supply' }}</p>
                        <a href="{{ route('website.products.show', $product) }}" class="mt-2 block min-h-10 text-[14px] font-extrabold leading-5 text-[#07215f] group-hover:text-emerald-700">{{ $product->name }}</a>
                        <p class="mt-3 text-[15px] font-extrabold text-slate-950">UGX {{ number_format($product->price) }}</p>
                        <form method="POST" action="{{ route('website.cart.store', $product) }}" class="mt-4">
                            @csrf
                            <button class="w-full rounded-md border border-[#d7e4ef] px-4 py-2 text-sm font-extrabold text-[#07215f] hover:border-emerald-500 hover:text-emerald-700">Add to Cart</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="border border-dashed border-slate-300 bg-white p-8 sm:col-span-2 lg:col-span-4">
                    <p class="font-semibold text-slate-950">No featured products yet.</p>
                    <p class="mt-1 text-sm text-slate-600">Featured products will appear here once the catalogue is updated.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <h2 class="text-[28px] font-extrabold text-[#07215f]">How EduKit works</h2>
            <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['1', 'Shop or upload list', 'Browse products or upload a school list.'],
                    ['2', 'Checkout and pay', 'Choose delivery and confirm charges.'],
                    ['3', 'We fulfil and deliver', 'EduKit coordinates suppliers and drivers.'],
                    ['4', 'Receive and confirm', 'Delivery is confirmed and the order is completed.'],
                ] as [$step, $title, $copy])
                    <div class="rounded-md border border-[#dbe8f3] bg-[#f7fbff] p-5">
                        <span class="grid size-9 place-items-center rounded-full bg-emerald-600 text-sm font-extrabold text-white">{{ $step }}</span>
                        <p class="mt-4 font-extrabold text-[#07215f]">{{ $title }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $copy }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h2 class="text-[28px] font-extrabold text-[#07215f]">Why choose EduKit?</h2>
        <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            @foreach (['Wide range of school products', 'Verified Ugandan suppliers', 'Convenient delivery options', 'Secure payment foundation', 'Trusted by parents and schools', 'Clear fulfilment tracking'] as $reason)
                <div class="rounded-md border border-[#dbe8f3] bg-white p-4 text-sm font-extrabold leading-6 text-[#07215f] shadow-sm">{{ $reason }}</div>
            @endforeach
        </div>
    </section>

    <section class="bg-emerald-50">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_420px] lg:items-center lg:px-8">
            <div>
                <p class="text-2xl font-black text-[#07215f]">Join parents preparing school needs with less stress.</p>
                <p class="mt-2 text-sm text-slate-700">Get the latest school supplies, offers and delivery updates.</p>
            </div>
            <form class="grid gap-2 sm:flex">
                <input type="email" placeholder="Enter your email address" class="min-w-0 flex-1 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                <button class="rounded bg-emerald-600 px-5 text-sm font-bold text-white hover:bg-emerald-700">Subscribe</button>
            </form>
        </div>
    </section>
@endsection
