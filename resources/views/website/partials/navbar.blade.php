<header class="sticky top-0 z-40 border-b border-[#dce8f2] bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-3 px-4 py-2.5 sm:px-6 lg:grid-cols-[260px_minmax(320px,540px)_auto] lg:justify-between lg:px-8">
        <a href="{{ route('website.home') }}" class="flex min-w-0 items-center">
            <img src="/images/website/edukit-store-logo.png" alt="EduKit Store" class="h-12 max-w-[180px] object-contain sm:h-14 sm:max-w-[220px] lg:h-16 lg:max-w-none">
        </a>

        <form method="GET" action="{{ route('website.products.index') }}" class="order-3 col-span-2 flex h-10 overflow-hidden rounded-md border border-[#d8e5f0] bg-white shadow-sm lg:order-none lg:col-span-1">
            <input name="search" value="{{ request('search') }}" placeholder="Search for school items, uniforms, books, stationery..." class="min-w-0 flex-1 border-0 px-3 text-[12px] font-semibold text-slate-600 placeholder:text-slate-400 focus:ring-0 sm:px-4">
            <button class="grid w-12 place-items-center bg-[#07215f] text-white hover:bg-emerald-700" aria-label="Search">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m21 21-4.3-4.3m2.3-5.2a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </form>

        <nav class="flex items-center justify-end gap-2 text-[12px] font-extrabold text-[#07215f] sm:gap-3 sm:text-sm">
            <a href="{{ route('website.cart.index') }}" class="relative grid size-9 place-items-center rounded-md hover:bg-slate-50 hover:text-emerald-700" aria-label="Cart">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 4h2l2.3 11.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.5L21 8H6.2M10 21h.01M18 21h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                @php($cartCount = array_sum(session('cart', [])))
                @if ($cartCount > 0)
                    <span class="absolute -right-1 -top-1 grid size-5 place-items-center rounded-full bg-emerald-600 text-[10px] font-black text-white">{{ $cartCount }}</span>
                @endif
            </a>
            <a href="{{ route('login') }}" class="hidden items-center gap-1 rounded-md px-2 py-2 hover:bg-slate-50 hover:text-emerald-700 min-[420px]:inline-flex">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Sign In
            </a>
            <a href="{{ route('website.join') }}" class="rounded-md bg-emerald-600 px-3 py-2 text-white shadow-sm hover:bg-emerald-700 sm:px-4">Join EduKit</a>
        </nav>
    </div>

    <div class="border-t border-[#eef4f8]">
        <nav class="no-scrollbar mx-auto flex max-w-7xl gap-7 overflow-x-auto px-4 py-2.5 text-[12px] font-extrabold text-[#19366f] sm:px-6 sm:text-[13px] lg:justify-center lg:px-8">
            <a href="{{ route('website.home') }}" class="{{ request()->routeIs('website.home') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Home</a>
            <a href="{{ route('website.products.index') }}" class="{{ request()->routeIs('website.products.*') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Shop</a>
            <a href="{{ route('website.upload-list') }}" class="{{ request()->routeIs('website.upload-list*') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Upload List</a>
            <a href="{{ route('website.schools') }}" class="{{ request()->routeIs('website.schools') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Schools</a>
            <a href="{{ route('website.home') }}#how-it-works" class="whitespace-nowrap border-b-2 border-transparent pb-2 hover:text-emerald-700">How It Works</a>
            <a href="{{ route('website.suppliers') }}" class="{{ request()->routeIs('website.suppliers*') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">For Suppliers</a>
            <a href="{{ route('website.drivers') }}" class="{{ request()->routeIs('website.drivers*') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">For Drivers</a>
            <a href="{{ route('website.schools') }}" class="whitespace-nowrap border-b-2 border-transparent pb-2 hover:text-emerald-700">For Schools</a>
            <a href="{{ route('website.track-order') }}" class="{{ request()->routeIs('website.track-order') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Track Order</a>
            <a href="{{ route('website.help') }}" class="{{ request()->routeIs('website.help') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Help</a>
        </nav>
    </div>
</header>
