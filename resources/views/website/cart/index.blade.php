@extends('website.layout')

@section('title', 'Cart - EduKit')

@section('content')
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Cart</p>
            <h1 class="mt-2 text-3xl font-black text-[#07215f]">Your school supply cart</h1>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-6 px-4 pb-14 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
        <div class="space-y-4">
            @if (session('status'))
                <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif

            @forelse ($products as $product)
                <article class="grid gap-4 rounded-md border border-[#dbe8f3] bg-white p-4 shadow-sm sm:grid-cols-[120px_1fr_auto]">
                    <div class="aspect-square overflow-hidden rounded-md bg-slate-50">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-2">
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $product->category?->name ?? 'School supply' }}</p>
                        <h2 class="mt-1 text-lg font-black text-[#07215f]">{{ $product->name }}</h2>
                        <p class="mt-2 text-sm text-slate-600">Quantity: {{ $product->cart_quantity }}</p>
                    </div>
                    <div class="flex items-start justify-between gap-4 sm:block sm:text-right">
                        <p class="font-black text-slate-950">UGX {{ number_format($product->cart_line_total) }}</p>
                        <form method="POST" action="{{ route('website.cart.destroy', $product) }}" class="mt-0 sm:mt-4">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm font-bold text-red-700 hover:text-red-900">Remove</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-md border border-dashed border-slate-300 bg-white p-8">
                    <p class="font-black text-[#07215f]">Your cart is empty.</p>
                    <p class="mt-2 text-sm text-slate-600">Start from the master catalogue and add school supplies.</p>
                    <a href="{{ route('website.products.index') }}" class="mt-5 inline-flex rounded-md bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Shop products</a>
                </div>
            @endforelse
        </div>

        <aside class="h-fit rounded-md border border-[#dbe8f3] bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-[#07215f]">Order summary</h2>
            <div class="mt-5 flex justify-between border-t border-slate-100 pt-4 text-sm">
                <span class="font-semibold text-slate-600">Subtotal</span>
                <span class="font-black text-slate-950">UGX {{ number_format($subtotal) }}</span>
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">Delivery and service fees will be calculated during checkout.</p>
            <a href="{{ route('website.track-order') }}" class="mt-5 flex justify-center rounded-md bg-[#07215f] px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Checkout coming soon</a>
        </aside>
    </section>
@endsection
