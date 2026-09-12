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
            @if ($errors->any())
                <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">Please check the highlighted cart details.</div>
            @endif

            @forelse ($products as $product)
                <article class="grid gap-4 rounded-md border border-[#dbe8f3] bg-white p-4 shadow-sm sm:grid-cols-[120px_1fr_220px]">
                    <div class="aspect-square overflow-hidden rounded-md bg-slate-50">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-2">
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $product->category?->name ?? 'School supply' }}</p>
                        <h2 class="mt-1 text-lg font-black text-[#07215f]">{{ $product->name }}</h2>
                        <p class="mt-2 text-sm text-slate-600">UGX {{ number_format($product->price) }} each</p>
                    </div>
                    <div class="space-y-3 sm:text-right">
                        <p class="font-black text-slate-950">UGX {{ number_format($product->cart_line_total) }}</p>
                        <form method="POST" action="{{ route('website.cart.update', $product) }}" class="flex items-center gap-2 sm:justify-end">
                            @csrf
                            @method('PATCH')
                            <label class="sr-only" for="cart-quantity-{{ $product->id }}">Quantity</label>
                            <input id="cart-quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ $product->stock_quantity }}" value="{{ $product->cart_quantity }}" class="h-10 w-20 rounded-md border-[#d7e4ef] text-center text-sm font-bold text-[#07215f] focus:border-emerald-600 focus:ring-emerald-600">
                            <button class="rounded-md border border-[#d7e4ef] px-3 py-2 text-xs font-bold text-[#07215f] hover:border-emerald-500">Update</button>
                        </form>
                        <form method="POST" action="{{ route('website.cart.destroy', $product) }}">
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
            <p class="mt-3 text-xs leading-5 text-slate-500">Delivery/convenience fee is added by EduKit after reviewing your delivery details. You pay after the invoice is ready.</p>

            @if ($products->isNotEmpty())
                <form method="POST" action="{{ route('website.cart.submit') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label for="parent_name" class="text-sm font-bold text-slate-700">Your name</label>
                        <input id="parent_name" name="parent_name" value="{{ old('parent_name') }}" required class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('parent_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="text-sm font-bold text-slate-700">Phone number</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="text-sm font-bold text-slate-700">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="school_name" class="text-sm font-bold text-slate-700">School</label>
                        <input id="school_name" name="school_name" value="{{ old('school_name') }}" class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div>
                            <label for="learner_name" class="text-sm font-bold text-slate-700">Learner</label>
                            <input id="learner_name" name="learner_name" value="{{ old('learner_name') }}" class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </div>
                        <div>
                            <label for="class_level" class="text-sm font-bold text-slate-700">Class</label>
                            <input id="class_level" name="class_level" value="{{ old('class_level') }}" class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </div>
                    </div>
                    <div>
                        <label for="delivery_preference" class="text-sm font-bold text-slate-700">Delivery option</label>
                        <select id="delivery_preference" name="delivery_preference" required class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="school" @selected(old('delivery_preference', 'school') === 'school')>Deliver to school</option>
                            <option value="home" @selected(old('delivery_preference') === 'home')>Deliver to home</option>
                            <option value="pickup" @selected(old('delivery_preference') === 'pickup')>Pickup point</option>
                        </select>
                    </div>
                    <div>
                        <label for="delivery_location" class="text-sm font-bold text-slate-700">Delivery location</label>
                        <textarea id="delivery_location" name="delivery_location" rows="3" class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('delivery_location') }}</textarea>
                    </div>
                    <div>
                        <label for="notes" class="text-sm font-bold text-slate-700">Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('notes') }}</textarea>
                    </div>
                    <button class="flex w-full justify-center rounded-md bg-[#07215f] px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Submit for invoice</button>
                </form>
            @endif
        </aside>
    </section>
@endsection
