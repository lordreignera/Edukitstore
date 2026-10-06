@extends('website.layout')

@section('title', 'Invoice '.$shoppingList->reference.' - EduKit')

@section('content')
    @php($payableItems = $shoppingList->lineItems->isNotEmpty() ? $shoppingList->lineItems : collect($shoppingList->cart_items ?? []))
    <section class="bg-white">
        <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-sm font-black uppercase tracking-wide text-emerald-700">Your invoice</p>
            <h1 class="mt-2 text-3xl font-black text-[#07215f]">{{ $shoppingList->reference }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                @if ($shoppingList->source === \App\Models\ShoppingList::SOURCE_CART)
                    Your order total includes the convenience fee shown at checkout. Review it below before paying.
                @else
                    EduKit reviews uploaded school lists, prepares the item total, then releases the invoice for payment.
                @endif
            </p>
        </div>
    </section>

    <section class="mx-auto grid max-w-5xl gap-6 px-4 pb-14 sm:px-6 lg:grid-cols-[1fr_340px] lg:px-8">
        <div class="space-y-4">
            @if (session('status'))
                <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
            @endif

            <section class="rounded-md border border-[#dbe8f3] bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-lg font-black text-[#07215f]">Items requested</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($payableItems as $item)
                        <div class="grid gap-3 px-5 py-4 text-sm sm:grid-cols-[1fr_auto]">
                            <div>
                                <p class="font-black text-slate-950">{{ data_get($item, 'product_name') ?? data_get($item, 'name') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ data_get($item, 'sku') }} | Qty {{ data_get($item, 'quantity') }} | UGX {{ number_format(data_get($item, 'unit_price')) }} each</p>
                            </div>
                            <p class="font-black text-slate-950">UGX {{ number_format(data_get($item, 'line_total')) }}</p>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-sm text-slate-500">This request was submitted as an uploaded shopping list.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-md border border-[#dbe8f3] bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-[#07215f]">Delivery details</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="font-bold text-slate-500">Name</dt><dd class="mt-1 text-slate-950">{{ $shoppingList->parent_name }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Phone</dt><dd class="mt-1 text-slate-950">{{ $shoppingList->phone }}</dd></div>
                    <div><dt class="font-bold text-slate-500">School</dt><dd class="mt-1 text-slate-950">{{ $shoppingList->school?->name ?? ($shoppingList->school_name ?: '-') }}</dd></div>
                    <div><dt class="font-bold text-slate-500">District</dt><dd class="mt-1 text-slate-950">{{ $shoppingList->school?->district?->name ?? $shoppingList->district?->name ?? '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Delivery</dt><dd class="mt-1 text-slate-950">{{ ucfirst($shoppingList->delivery_preference) }}</dd></div>
                    <div class="sm:col-span-2"><dt class="font-bold text-slate-500">Location</dt><dd class="mt-1 text-slate-950">{{ $shoppingList->delivery_location ?: '-' }}</dd></div>
                </dl>
            </section>

            @if ($shoppingList->assignedDriver)
                <section class="rounded-md border border-[#dbe8f3] bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-[#07215f]">Assigned delivery partner</h2>
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="font-bold text-slate-500">Driver</dt><dd class="mt-1 text-slate-950">{{ $shoppingList->assignedDriver->name }}</dd></div>
                        <div><dt class="font-bold text-slate-500">Phone</dt><dd class="mt-1 text-slate-950">{{ $shoppingList->assignedDriver->phone ?: '-' }}</dd></div>
                        <div><dt class="font-bold text-slate-500">Vehicle</dt><dd class="mt-1 text-slate-950">{{ $shoppingList->assignedDriver->vehicle_type ?: '-' }}</dd></div>
                        <div><dt class="font-bold text-slate-500">Registration</dt><dd class="mt-1 text-slate-950">{{ $shoppingList->assignedDriver->vehicle_registration ?: '-' }}</dd></div>
                    </dl>
                </section>
            @endif
        </div>

        <aside class="h-fit rounded-md border border-[#dbe8f3] bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-[#07215f]">Review your payment</h2>
            <p class="mt-1 text-xs text-slate-500">Check the items and quantities before paying.</p>
            <div class="mt-4 divide-y divide-slate-100 border-y border-slate-100 text-sm">
                @foreach ($payableItems as $item)
                    <div class="flex justify-between gap-3 py-2">
                        <span class="text-slate-700">{{ data_get($item, 'product_name') ?? data_get($item, 'name') }} × {{ data_get($item, 'quantity') }}</span>
                        <span class="shrink-0 font-bold">UGX {{ number_format(data_get($item, 'line_total')) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-sm">
                <div class="flex justify-between"><span class="font-semibold text-slate-600">Items subtotal</span><span class="font-black text-slate-950">UGX {{ number_format($shoppingList->items_subtotal) }}</span></div>
                <div class="flex justify-between"><span class="font-semibold text-slate-600">Convenience fee</span><span class="font-black text-slate-950">{{ $shoppingList->delivery_fee === null ? 'Pending' : 'UGX '.number_format($shoppingList->delivery_fee) }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-3 text-base"><span class="font-black text-[#07215f]">Total</span><span class="font-black text-[#07215f]">{{ $shoppingList->estimated_total ? 'UGX '.number_format($shoppingList->estimated_total) : 'Pending' }}</span></div>
            </div>

            <div class="mt-5 rounded-md border border-slate-200 bg-slate-50 p-4 text-sm">
                <p class="font-black text-slate-900">Status: {{ \App\Models\ShoppingList::statuses()[$shoppingList->status] ?? ucfirst($shoppingList->status) }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Payment: {{ $shoppingList->payment_provider === 'demo' ? 'Demo paid — no money collected' : ucfirst($shoppingList->payment_status) }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Delivery: {{ $shoppingList->delivery_confirmed_at ? 'Confirmed '.$shoppingList->delivery_confirmed_at->format('M d, Y') : 'Awaiting driver confirmation' }}</p>
            </div>

            @if ($shoppingList->payment_exception)
                <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4 text-xs font-semibold text-amber-900">
                    @switch($shoppingList->payment_exception_type)
                        @case('stock')
                            Payment was received. Your order needs a stock review before delivery. EduKit will contact you.
                            @break
                        @case('duplicate')
                            An additional payment needs review. EduKit will contact you about the duplicate charge.
                            @break
                        @case('late_payment')
                            Payment arrived after this order closed. EduKit will contact you about the next steps or a refund.
                            @break
                        @default
                            Payment details need review. Please do not retry payment until EduKit confirms the transaction.
                    @endswitch
                </p>
            @endif

            @if ($shoppingList->status === \App\Models\ShoppingList::STATUS_QUOTED && $shoppingList->estimated_total && $shoppingList->payment_status !== \App\Models\ShoppingList::PAYMENT_PAID)
                <form method="POST" action="{{ route('website.quote.pay', $shoppingList->reference) }}" class="mt-5">
                    @csrf
                    <label class="mb-3 flex items-start gap-2 text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="confirm_items" value="1" required class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                        <span>I checked the items, quantities, convenience fee and total above.</span>
                    </label>
                    @error('confirm_items') <p class="mb-3 text-xs font-semibold text-red-700">{{ $message }}</p> @enderror
                    @if (\App\Support\PaymentMode::demoEnabled())
                        <p class="mb-3 rounded border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-900">Demo mode: this records a test payment and collects no money.</p>
                    @endif
                    <button class="flex w-full justify-center rounded-md bg-emerald-600 px-5 py-3 text-sm font-black text-white hover:bg-emerald-700">{{ \App\Support\PaymentMode::demoEnabled() ? 'Complete demo payment' : 'Pay with Flutterwave' }}</button>
                </form>
            @else
                <p class="mt-5 rounded-md border border-amber-200 bg-amber-50 p-4 text-xs font-semibold leading-5 text-amber-900">{{ $shoppingList->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? ($shoppingList->payment_provider === 'demo' ? 'Demo payment recorded. No money was collected.' : 'Payment received.') : 'Payment opens when this invoice is ready for payment.' }}</p>
            @endif
        </aside>
    </section>
@endsection
