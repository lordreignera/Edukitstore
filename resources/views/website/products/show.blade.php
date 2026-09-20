@extends('website.layout')

@section('title', $product->name . ' - EduKit')

@section('content')
    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm">
            <div class="aspect-[4/3] bg-slate-100">
                <x-ui.responsive-image
                    :src="$product->image_url"
                    :alt="$product->name"
                    :label="$product->category?->name ?? 'EduKit Supply'"
                    image-class="h-full w-full object-contain p-6"
                    fallback-class="grid h-full w-full place-items-center bg-emerald-50 px-4 text-center text-lg font-bold text-emerald-800"
                />
            </div>
        </div>

        <div>
            <p class="text-[11px] font-extrabold uppercase text-emerald-700">{{ $product->category?->name ?? 'School supply' }}</p>
            <h1 class="mt-3 text-[30px] font-extrabold leading-tight text-[#07215f] sm:text-[38px]">{{ $product->name }}</h1>
            <p class="mt-4 text-[24px] font-extrabold text-slate-950 sm:text-[28px]">From UGX {{ number_format($product->marketplace_price) }}</p>
            <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded border border-slate-200 bg-white p-4">
                    <dt class="font-semibold text-slate-500">Product code</dt>
                    <dd class="mt-1 text-slate-950">{{ $product->sku }}</dd>
                </div>
                <div class="rounded border border-slate-200 bg-white p-4">
                    <dt class="font-semibold text-slate-500">Available for sale</dt>
                    <dd class="mt-1 text-slate-950">{{ $product->marketplace_stock_quantity }}</dd>
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
            @if ($product->marketplace_stock_quantity > 0)
                <div class="mt-8 space-y-3"><h2 class="text-sm font-extrabold text-[#07215f]">Choose fulfilment source</h2>
                    @if($product->stock_quantity > 0)<form method="POST" action="{{ route('website.cart.store',$product) }}" class="grid gap-3 rounded-md border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_100px_auto] sm:items-center">@csrf<div><p class="font-bold text-[#07215f]">Fulfilled by EduKit</p><p class="text-xs text-slate-500">From the EduKit warehouse | {{ number_format($product->stock_quantity) }} available</p><p class="mt-1 font-black">UGX {{ number_format($product->price) }}</p></div><input name="quantity" type="number" min="1" max="{{ $product->stock_quantity }}" value="1" class="rounded-md border-slate-300 text-center"><button class="rounded-md bg-emerald-700 px-4 py-3 text-sm font-bold text-white">Add</button></form>@endif
                    @foreach($product->approvedSupplierOffers as $offer)<form method="POST" action="{{ route('website.cart.store',$product) }}" class="grid gap-3 rounded-md border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_100px_auto] sm:items-center">@csrf<input type="hidden" name="supplier_offer_id" value="{{ $offer->id }}"><div><p class="font-bold text-[#07215f]">Dispatched by {{ $offer->supplier->business_name }}</p><p class="text-xs text-slate-500">Supplier warehouse in {{ $offer->supplier->district ?: 'Uganda' }} | {{ number_format($offer->quantity_available) }} available</p><p class="mt-1 font-black">UGX {{ number_format($offer->customer_price) }}</p></div><input name="quantity" type="number" min="1" max="{{ $offer->quantity_available }}" value="1" class="rounded-md border-slate-300 text-center"><button class="rounded-md bg-emerald-700 px-4 py-3 text-sm font-bold text-white">Add</button></form>@endforeach
                </div>
            @else
                <div class="mt-8 flex flex-wrap gap-3">
                    <span class="inline-flex w-full justify-center rounded-md bg-slate-100 px-5 py-3 text-sm font-extrabold text-slate-500 sm:w-auto">Out of stock</span>
                </div>
            @endif
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('website.products.index') }}" class="inline-flex w-full justify-center rounded-md border border-[#d7e4ef] bg-white px-5 py-3 text-sm font-extrabold text-[#07215f] hover:border-emerald-500 sm:w-auto">Back to products</a>
            </div>
        </div>
    </section>
@endsection
