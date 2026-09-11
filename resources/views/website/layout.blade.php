<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', config('app.name', 'EduKit'))</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f6fbff] text-slate-950 antialiased" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
        <header class="sticky top-0 z-40 border-b border-[#dce8f2] bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-3 px-4 py-2.5 sm:px-6 lg:grid-cols-[210px_1fr_auto] lg:px-8">
                <a href="{{ route('website.home') }}" class="flex min-w-0 items-center">
                    <img src="/images/website/edukit-logo.svg" alt="EduKit" class="h-10 w-auto sm:h-11">
                </a>

                <form method="GET" action="{{ route('website.products.index') }}" class="order-3 col-span-2 flex h-10 overflow-hidden rounded-md border border-[#d8e5f0] bg-white shadow-sm lg:order-none lg:col-span-1">
                    <input name="search" value="{{ request('search') }}" placeholder="Search for school items, uniforms, books, stationery..." class="min-w-0 flex-1 border-0 px-3 text-[13px] font-semibold text-slate-700 placeholder:text-slate-400 focus:ring-0 sm:px-4">
                    <button class="grid w-12 place-items-center bg-[#07215f] text-sm font-extrabold text-white hover:bg-emerald-700" aria-label="Search">
                        <span aria-hidden="true">Go</span>
                    </button>
                </form>

                <nav class="flex items-center justify-end gap-2 text-[12px] font-extrabold text-[#07215f] sm:gap-4 sm:text-sm">
                    <a href="{{ route('website.cart.index') }}" class="relative rounded-md px-2 py-2 hover:bg-slate-50 hover:text-emerald-700">
                        Cart
                        @php($cartCount = array_sum(session('cart', [])))
                        @if ($cartCount > 0)
                            <span class="absolute -right-2 -top-1 grid size-5 place-items-center rounded-full bg-emerald-600 text-[10px] font-black text-white">{{ $cartCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('login') }}" class="hidden rounded-md px-2 py-2 hover:bg-slate-50 hover:text-emerald-700 min-[420px]:inline">Sign In</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-emerald-600 px-3 py-2 text-white shadow-sm hover:bg-emerald-700 sm:px-4">Register</a>
                </nav>
            </div>

            <div class="border-t border-[#eef4f8]">
                <nav class="no-scrollbar mx-auto flex max-w-7xl gap-6 overflow-x-auto px-4 py-2.5 text-[12px] font-extrabold text-[#19366f] sm:px-6 sm:text-[13px] lg:justify-center lg:px-8">
                    <a href="{{ route('website.home') }}" class="{{ request()->routeIs('website.home') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Home</a>
                    <a href="{{ route('website.products.index') }}" class="{{ request()->routeIs('website.products.*') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Shop</a>
                    <a href="{{ route('website.upload-list') }}" class="{{ request()->routeIs('website.upload-list*') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Upload List</a>
                    <a href="{{ route('website.schools') }}" class="{{ request()->routeIs('website.schools') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Schools</a>
                    <a href="{{ route('website.suppliers') }}" class="{{ request()->routeIs('website.suppliers*') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">For Suppliers</a>
                    <a href="{{ route('website.track-order') }}" class="{{ request()->routeIs('website.track-order') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Track Order</a>
                    <a href="{{ route('website.help') }}" class="{{ request()->routeIs('website.help') ? 'border-emerald-500 text-emerald-700' : 'border-transparent hover:text-emerald-700' }} whitespace-nowrap border-b-2 pb-2">Help</a>
                </nav>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-[13px] text-slate-600 sm:grid-cols-2 sm:px-6 lg:grid-cols-[1.5fr_0.8fr_0.9fr_0.9fr_1fr] lg:px-8">
                <div>
                    <img src="/images/website/edukit-logo.svg" alt="EduKit" class="h-11 w-auto">
                    <p class="mt-3 max-w-xs leading-6">EduKit connects parents, schools, suppliers and delivery partners to make school preparation simpler across Uganda.</p>
                </div>
                <div>
                    <p class="font-extrabold text-[#07215f]">Quick Links</p>
                    <div class="mt-3 grid gap-2">
                        <a href="{{ route('website.home') }}" class="hover:text-emerald-700">Home</a>
                        <a href="{{ route('website.products.index') }}" class="hover:text-emerald-700">Shop</a>
                        <a href="{{ route('website.upload-list') }}" class="hover:text-emerald-700">Upload List</a>
                        <a href="{{ route('website.track-order') }}" class="hover:text-emerald-700">Track Order</a>
                    </div>
                </div>
                <div>
                    <p class="font-extrabold text-[#07215f]">For Suppliers</p>
                    <div class="mt-3 grid gap-2">
                        <a href="{{ route('website.suppliers') }}" class="hover:text-emerald-700">Become a Supplier</a>
                        <a href="{{ route('login') }}" class="hover:text-emerald-700">Supplier Login</a>
                    </div>
                </div>
                <div>
                    <p class="font-extrabold text-[#07215f]">For Schools</p>
                    <div class="mt-3 grid gap-2">
                        <a href="{{ route('website.schools') }}" class="hover:text-emerald-700">School Portal</a>
                        <a href="{{ route('website.schools') }}" class="hover:text-emerald-700">Partnerships</a>
                        <a href="{{ route('website.schools') }}" class="hover:text-emerald-700">Delivery Information</a>
                    </div>
                </div>
                <div>
                    <p class="font-extrabold text-[#07215f]">Contact Us</p>
                    <div class="mt-3 grid gap-2">
                        <p>+256 700 123456</p>
                        <p>support@edukit.test</p>
                        <p>Kampala, Uganda</p>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-100">
                <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-[12px] font-semibold text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <p>&copy; {{ date('Y') }} EduKit. All rights reserved.</p>
                    <p>Better education. Brighter futures.</p>
                </div>
            </div>
        </footer>
        @stack('scripts')
    </body>
</html>
