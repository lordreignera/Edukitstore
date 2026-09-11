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
    <body class="bg-[#f7fbff] text-slate-950 antialiased" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
        <header class="sticky top-0 z-30 border-b border-[#dbe8f3] bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-3 px-4 py-3 sm:px-6 lg:grid-cols-[210px_1fr_auto] lg:px-8">
                <a href="{{ route('website.home') }}" class="flex items-center">
                    <img src="/images/website/edukit-logo.svg" alt="EduKit" class="h-10 w-auto sm:h-11">
                </a>

                <form method="GET" action="{{ route('website.products.index') }}" class="col-span-2 flex h-11 overflow-hidden rounded-md border border-[#d8e5f0] bg-white shadow-sm lg:col-span-1">
                    <input name="search" value="{{ request('search') }}" placeholder="Search for school items, uniforms, books, stationery..." class="min-w-0 flex-1 border-0 px-4 text-sm font-medium text-slate-700 placeholder:text-slate-400 focus:ring-0">
                    <button class="bg-[#07215f] px-4 text-sm font-extrabold text-white hover:bg-emerald-700 sm:px-5">Search</button>
                </form>

                <nav class="flex items-center justify-end gap-3 text-[13px] font-bold text-[#07215f] sm:gap-4 sm:text-sm">
                    <a href="{{ route('website.cart.index') }}" class="relative hover:text-emerald-700">
                        Cart
                        @php($cartCount = array_sum(session('cart', [])))
                        @if ($cartCount > 0)
                            <span class="absolute -right-4 -top-3 grid size-5 place-items-center rounded-full bg-emerald-600 text-[10px] font-black text-white">{{ $cartCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('login') }}" class="hidden hover:text-emerald-700 min-[420px]:inline">Sign In</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-emerald-600 px-3 py-2 text-white shadow-sm hover:bg-emerald-700 sm:px-4">Register</a>
                </nav>
            </div>
            <div class="border-t border-[#eef4f8]">
                <nav class="mx-auto flex max-w-7xl gap-7 overflow-x-auto px-4 py-3 text-[13px] font-extrabold text-[#19366f] sm:px-6 lg:justify-center lg:px-8">
                    <a href="{{ route('website.home') }}" class="whitespace-nowrap border-b-2 border-emerald-500 pb-2 text-emerald-700">Home</a>
                    <a href="{{ route('website.products.index') }}" class="whitespace-nowrap pb-2 hover:text-emerald-700">Shop</a>
                    <a href="{{ route('website.upload-list') }}" class="whitespace-nowrap pb-2 hover:text-emerald-700">Upload List</a>
                    <a href="{{ route('website.schools') }}" class="whitespace-nowrap pb-2 hover:text-emerald-700">Schools</a>
                    <a href="{{ route('website.suppliers') }}" class="whitespace-nowrap pb-2 hover:text-emerald-700">For Suppliers</a>
                    <a href="{{ route('website.track-order') }}" class="whitespace-nowrap pb-2 hover:text-emerald-700">Track Order</a>
                    <a href="{{ route('website.help') }}" class="whitespace-nowrap pb-2 hover:text-emerald-700">Help</a>
                </nav>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-sm text-slate-600 sm:grid-cols-2 sm:px-6 lg:grid-cols-5 lg:px-8">
                <div>
                    <p class="text-xl font-black text-[#07215f]">EduKit</p>
                    <p class="mt-1">Parents order. Suppliers fulfil. Drivers deliver.</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-950">Quick Links</p>
                    <div class="mt-2 grid gap-1">
                        <a href="{{ route('website.home') }}" class="hover:text-emerald-700">Home</a>
                        <a href="{{ route('website.products.index') }}" class="hover:text-emerald-700">Shop</a>
                        <a href="{{ route('website.upload-list') }}" class="hover:text-emerald-700">Upload List</a>
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-slate-950">For Suppliers</p>
                    <div class="mt-2 grid gap-1">
                        <a href="{{ route('website.suppliers') }}" class="hover:text-emerald-700">Become a Supplier</a>
                        <a href="{{ route('login') }}" class="hover:text-emerald-700">Supplier Login</a>
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-slate-950">For Schools</p>
                    <div class="mt-2 grid gap-1">
                        <a href="{{ route('website.schools') }}" class="hover:text-emerald-700">School Portal</a>
                        <a href="{{ route('website.schools') }}" class="hover:text-emerald-700">Delivery Information</a>
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-slate-950">Contact Us</p>
                    <p class="mt-2">Kampala, Uganda</p>
                    <p>support@edukit.test</p>
                    <p>UGX pricing</p>
                </div>
            </div>
        </footer>
    </body>
</html>
