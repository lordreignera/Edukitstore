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
    <body @if (auth()->check() || request()->routeIs('website.quote.show')) data-private-page @endif class="overflow-x-hidden bg-[#f6fbff] text-slate-950 antialiased" style="font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;">
        @include('website.partials.navbar')

        @if (session('cart_added'))
            <div role="status" class="border-b border-emerald-200 bg-emerald-50">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm font-bold text-emerald-900 sm:px-6 lg:px-8">
                    <span>{{ session('cart_added') }}</span>
                    <a href="{{ route('website.cart.index') }}#order-details" class="rounded-md bg-emerald-700 px-4 py-2 text-white hover:bg-emerald-800">Checkout</a>
                </div>
            </div>
        @endif

        <main>
            @yield('content')
        </main>

        @include('website.partials.footer')
        @stack('scripts')
    </body>
</html>
