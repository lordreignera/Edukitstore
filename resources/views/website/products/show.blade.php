@extends('website.layout')

@section('title', $product->name . ' - EduKit')

@section('content')
    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm">
            <div class="aspect-[4/3] bg-slate-100">
                @if ($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-6">
                @else
                    <div class="grid h-full place-items-center bg-emerald-50 text-lg font-bold text-emerald-800">EduKit Supply</div>
                @endif
            </div>
        </div>

        <div>
            <p class="text-[11px] font-extrabold uppercase text-emerald-700">{{ $product->category?->name ?? 'School supply' }}</p>
            <h1 class="mt-3 text-[38px] font-extrabold leading-tight text-[#07215f]">{{ $product->name }}</h1>
            <p class="mt-4 text-[28px] font-extrabold text-slate-950">UGX {{ number_format($product->price) }}</p>
            <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded border border-slate-200 bg-white p-4">
                    <dt class="font-semibold text-slate-500">Product code</dt>
                    <dd class="mt-1 text-slate-950">{{ $product->sku }}</dd>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <dt class="font-semibold text-slate-500">Available stock</dt>
                    <dd class="mt-1 text-slate-950">{{ $product->stock_quantity }}</dd>
                </div>
                @if ($product->brand)
                    <div class="rounded border border-slate-200 bg-white p-4">
                        <dt class="font-semibold text-slate-500">Brand</dt>
                        <dd class="mt-1 text-slate-950">{{ $product->brand }}</dd>
                    </div>
                @endif
                @if ($product->unit)
                    <div class="rounded border border-slate-200 bg-white p-4">
                        <dt class="font-semibold text-slate-500">Unit</dt>
                        <dd class="mt-1 text-slate-950">{{ $product->unit }}</dd>
                    </div>
                @endif
            </dl>
            @if ($product->description)
                <p class="mt-6 leading-7 text-slate-700">{{ $product->description }}</p>
            @endif
            <div class="mt-8 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('website.cart.store', $product) }}" class="flex flex-wrap gap-3">
                    @csrf
                    <label class="sr-only" for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="1" max="{{ $product->stock_quantity }}" value="1" class="w-28 rounded-md border-[#d7e4ef] text-center text-sm font-bold text-[#07215f] focus:border-emerald-600 focus:ring-emerald-600">
                    <button class="rounded-md bg-emerald-600 px-5 py-3 text-sm font-extrabold text-white hover:bg-emerald-700">Add to cart</button>
                </form>
                <a href="{{ route('website.products.index') }}" class="inline-flex rounded-md border border-[#d7e4ef] bg-white px-5 py-3 text-sm font-extrabold text-[#07215f] hover:border-emerald-500">Back to products</a>
            </div>
        </div>
    </section>
@endsection
