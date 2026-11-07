@extends('website.layout')

@section('title', 'Invoice '.$shoppingList->reference.' - EduKit')

@section('content')
    @php($payableItems = $shoppingList->lineItems->isNotEmpty() ? $shoppingList->lineItems : collect($shoppingList->cart_items ?? []))
    @php($journeyStatus = $shoppingList->deliveryStatus())
    @php($emailParts = explode('@', (string) $shoppingList->email, 2))
    @php($maskedOrderEmail = count($emailParts) === 2 && $emailParts[0] !== '' ? substr($emailParts[0], 0, 1).'***@'.$emailParts[1] : 'Hidden for privacy')
    <section class="bg-white">
        <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-sm font-black uppercase tracking-wide text-orange-700">Your invoice</p>
            <h1 class="mt-2 text-3xl font-black text-[#07215f]">{{ $shoppingList->reference }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                @if ($shoppingList->customer_user_id && auth()->id() === $shoppingList->customer_user_id)
                    This order is saved to your EduKit account. You can return to it any time from My Orders.
                @elseif (! $shoppingList->customer_user_id)
                    You can pay for this order without an account. To track it later, create or sign in to an account using this order email and verify that address.
                @elseif ($shoppingList->payment_status === \App\Models\ShoppingList::PAYMENT_PAID)
                    Payment received. Follow delivery progress below.
                @elseif ($shoppingList->source === \App\Models\ShoppingList::SOURCE_CART)
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
                <div role="status" class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
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
                                <p class="mt-1 text-xs text-slate-500">{{ data_get($item, 'sku') }} | {{ data_get($item, 'ordered_quantity') && (int) data_get($item, 'ordered_quantity') !== (int) data_get($item, 'quantity') ? 'Delivering '.data_get($item, 'quantity').' of '.data_get($item, 'ordered_quantity') : 'Qty '.data_get($item, 'quantity') }} | UGX {{ number_format(data_get($item, 'unit_price')) }} each</p>
                                @if ((int) data_get($item, 'unavailable_quantity') > 0)
                                    <p class="mt-1 text-xs font-bold text-red-700">{{ data_get($item, 'unavailable_quantity') }} unavailable — {{ \App\Models\ShoppingListItem::unavailableReasons()[data_get($item, 'unavailable_reason')] ?? 'availability issue' }}{{ data_get($item, 'unavailable_note') ? ': '.data_get($item, 'unavailable_note') : '' }}</p>
                                @endif
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
                    <div><dt class="font-bold text-slate-500">Mobile Money phone</dt><dd class="mt-1 text-slate-950">{{ $shoppingList->phone }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Order email</dt><dd class="mt-1 break-all text-slate-950" aria-label="Order email partially hidden for privacy">{{ $maskedOrderEmail }}</dd></div>
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

            <section class="rounded-md border border-[#dbe8f3] bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-[#07215f]">Delivery progress</h2>
                        <p class="mt-1 text-sm text-slate-600">You can follow each handover step here.</p>
                    </div>
                    <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-black text-orange-700">{{ \App\Models\ShoppingList::deliveryStatuses()[$journeyStatus] ?? ucfirst($journeyStatus) }}</span>
                </div>
                <ol class="mt-5 grid gap-4 sm:grid-cols-5">
                    @foreach ([
                        ['label' => 'Paid', 'done' => $shoppingList->payment_status === \App\Models\ShoppingList::PAYMENT_PAID, 'time' => $shoppingList->paid_at],
                        ['label' => 'Packed', 'done' => (bool) $shoppingList->packed_at, 'time' => $shoppingList->packed_at],
                        ['label' => 'Driver assigned', 'done' => (bool) $shoppingList->assigned_driver_id, 'time' => null],
                        ['label' => 'In transit', 'done' => (bool) $shoppingList->driver_started_at, 'time' => $shoppingList->driver_started_at],
                        ['label' => 'Driver reached', 'done' => (bool) $shoppingList->driver_reached_at, 'time' => $shoppingList->driver_reached_at],
                        ['label' => 'Received', 'done' => (bool) ($shoppingList->customer_received_at || $shoppingList->delivery_confirmed_at), 'time' => $shoppingList->customer_received_at ?: $shoppingList->delivery_confirmed_at],
                    ] as $step)
                        <li class="flex items-start gap-2 text-sm">
                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full {{ $step['done'] ? 'bg-orange-600 text-white' : 'border border-slate-300 text-slate-400' }}">{{ $step['done'] ? '✓' : '•' }}</span>
                            <span><strong class="block {{ $step['done'] ? 'text-slate-900' : 'text-slate-500' }}">{{ $step['label'] }}</strong>@if ($step['time'])<small class="text-xs text-slate-500">{{ $step['time']->format('d M, H:i') }}</small>@elseif (! $step['done'])<small class="text-xs text-slate-400">Pending</small>@endif</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="h-fit rounded-md border border-[#dbe8f3] bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-[#07215f]">{{ $shoppingList->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? 'Order summary' : 'Review your payment' }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ $shoppingList->customer_user_id && auth()->id() === $shoppingList->customer_user_id ? 'This order is also available in your account.' : ($shoppingList->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? 'Keep this order page open on this device for now.' : 'Check the items and quantities before paying.') }}</p>
            <div class="mt-4 divide-y divide-slate-100 border-y border-slate-100 text-sm">
                @foreach ($payableItems as $item)
                    <div class="flex justify-between gap-3 py-2">
                        <span class="text-slate-700">{{ data_get($item, 'product_name') ?? data_get($item, 'name') }} × {{ data_get($item, 'quantity') }}</span>
                        <span class="shrink-0 font-bold">UGX {{ number_format(data_get($item, 'line_total')) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-sm">
                <div class="flex justify-between"><span class="font-semibold text-slate-600">{{ $shoppingList->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? 'Items being delivered' : 'Items subtotal' }}</span><span class="font-black text-slate-950">UGX {{ number_format($shoppingList->lineItems->isNotEmpty() && $shoppingList->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? $shoppingList->lineItems->sum('line_total') : $shoppingList->items_subtotal) }}</span></div>
                <div class="flex justify-between"><span class="font-semibold text-slate-600">Convenience fee</span><span class="font-black text-slate-950">{{ $shoppingList->delivery_fee === null ? 'Pending' : 'UGX '.number_format($shoppingList->delivery_fee) }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-3 text-base"><span class="font-black text-[#07215f]">Total</span><span class="font-black text-[#07215f]">{{ $shoppingList->estimated_total ? 'UGX '.number_format($shoppingList->estimated_total) : 'Pending' }}</span></div>
                @if ($shoppingList->manualRefundDue() > 0)
                    <div class="rounded border border-orange-200 bg-orange-50 p-3 text-xs leading-5 text-orange-950">
                        @if ($shoppingList->manual_refunded_at)
                            {{ $shoppingList->payment_provider === 'demo' ? 'Dry-run refund recorded' : 'Manual refund recorded' }}: <strong>UGX {{ number_format($shoppingList->manual_refund_amount) }}</strong> (reference {{ $shoppingList->manual_refund_reference }}).{{ $shoppingList->payment_provider === 'demo' ? ' No money was collected or returned.' : '' }}
                        @else
                            {{ $shoppingList->payment_provider === 'demo' ? 'Demo refund record pending' : 'Manual refund pending' }}: <strong>UGX {{ number_format($shoppingList->manualRefundOutstanding()) }}</strong>. EduKit is delivering the available items; the unavailable items and any eligible fee refund are listed above.{{ $shoppingList->payment_provider === 'demo' ? ' No money was collected or returned in demo mode.' : '' }}
                        @endif
                    </div>
                @endif
            </div>

            <div class="mt-5 rounded-md border border-slate-200 bg-slate-50 p-4 text-sm">
                <p class="font-black text-slate-900">Fulfilment: {{ $shoppingList->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? $shoppingList->fulfilmentStatusLabel() : (\App\Models\ShoppingList::statuses()[$shoppingList->status] ?? ucfirst($shoppingList->status)) }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Payment: {{ $shoppingList->payment_provider === 'demo' ? ($shoppingList->payment_status === \App\Models\ShoppingList::PAYMENT_REFUNDED ? 'Demo refund recorded — no funds collected or returned' : 'Demo paid — no money collected') : ucfirst($shoppingList->payment_status) }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Delivery: {{ \App\Models\ShoppingList::deliveryStatuses()[$journeyStatus] ?? ucfirst($journeyStatus) }}</p>
            </div>

            @if (! $shoppingList->customer_user_id)
                <div class="mt-5 rounded-xl border border-orange-200 bg-orange-50 p-4">
                    <h3 class="font-black text-[#07215f]">Want to track this order later?</h3>
                    <p class="mt-1 text-xs leading-5 text-slate-700">You can order as a guest. To add this order to My Orders later, create an account or sign in using the same email you entered at checkout, then verify that email address.</p>
                    <div class="mt-3 grid gap-2">
                        <a href="{{ route('customer.register') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-orange-600 px-4 py-2 text-sm font-black text-white hover:bg-orange-700">Create an account</a>
                        <a href="{{ route('login') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-orange-300 bg-white px-4 py-2 text-sm font-bold text-[#07215f] hover:bg-orange-100">Sign in</a>
                    </div>
                </div>
            @endif

            @if ($journeyStatus === 'awaiting_customer_confirmation')
                <form method="POST" action="{{ route('website.quote.received', $shoppingList->reference) }}" class="mt-5 rounded-md border border-orange-200 bg-orange-50 p-4">
                    @csrf
                    <h3 class="font-black text-orange-950">Have you received the items being delivered?</h3>
                    <p class="mt-1 text-xs leading-5 text-orange-900">Unavailable items are excluded from this delivery and handled through the refund update above.</p>
                    <label class="mt-3 flex items-start gap-2 text-xs font-semibold text-orange-950"><input type="checkbox" name="items_received" value="1" required class="mt-0.5 rounded border-orange-300 text-orange-600 focus:ring-orange-600"><span>I received all items marked for delivery in good order.</span></label>
                    @error('items_received') <p class="mt-1 text-xs font-semibold text-red-700">{{ $message }}</p> @enderror
                    <label class="mt-3 block text-xs font-bold text-orange-950" for="received_name">Received by</label>
                    <input id="received_name" name="received_name" value="{{ old('received_name', $shoppingList->parent_name) }}" required class="mt-1 w-full rounded-md border-orange-200 text-sm focus:border-orange-600 focus:ring-orange-600">
                    @error('received_name') <p class="mt-1 text-xs font-semibold text-red-700">{{ $message }}</p> @enderror
                    <label class="mt-3 block text-xs font-bold text-orange-950" for="received_notes">Note <span class="font-normal">(optional)</span></label>
                    <textarea id="received_notes" name="received_notes" rows="2" class="mt-1 w-full rounded-md border-orange-200 text-sm focus:border-orange-600 focus:ring-orange-600">{{ old('received_notes') }}</textarea>
                    <button class="mt-3 w-full rounded-md bg-orange-700 px-4 py-3 text-sm font-black text-white hover:bg-orange-800">Confirm received</button>
                </form>
            @elseif ($shoppingList->customer_received_at || $shoppingList->delivery_confirmed_at)
                <p class="mt-5 rounded-md border border-orange-200 bg-orange-50 p-4 text-xs font-semibold leading-5 text-orange-900">Receipt confirmed{{ $shoppingList->customer_received_name ? ' by '.$shoppingList->customer_received_name : '' }}. This order is complete.</p>
            @endif

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

            @if ($shoppingList->status === \App\Models\ShoppingList::STATUS_QUOTED && $shoppingList->estimated_total && $shoppingList->payment_status !== \App\Models\ShoppingList::PAYMENT_PAID && (\App\Support\PaymentMode::demoEnabled() || config('services.online_payment_provider', 'pesapal') === 'flutterwave'))
                <form method="POST" action="{{ route('website.quote.pay', $shoppingList->reference) }}" class="mt-5">
                    @csrf
                    <label class="mb-3 flex items-start gap-2 text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="confirm_items" value="1" required class="mt-0.5 rounded border-slate-300 text-orange-600 focus:ring-orange-600">
                        <span>I checked the items, quantities, convenience fee and total above.</span>
                    </label>
                    @error('confirm_items') <p class="mb-3 text-xs font-semibold text-red-700">{{ $message }}</p> @enderror
                    @if (\App\Support\PaymentMode::demoEnabled())
                        <p class="mb-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-900">Demo mode: this records a test payment and collects no money. Live online payments are planned through Pesapal after merchant setup.</p>
                    @endif
                    <button class="flex w-full justify-center rounded-lg bg-orange-600 px-5 py-3 text-sm font-black text-white hover:bg-orange-700">{{ \App\Support\PaymentMode::demoEnabled() ? 'Complete demo payment' : 'Pay securely online' }}</button>
                </form>
            @elseif ($shoppingList->status === \App\Models\ShoppingList::STATUS_QUOTED && $shoppingList->estimated_total && $shoppingList->payment_status !== \App\Models\ShoppingList::PAYMENT_PAID && config('services.online_payment_provider', 'pesapal') === 'pesapal')
                <div role="status" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                    <h3 class="font-black">Pesapal payment is not available yet</h3>
                    <p class="mt-1 text-xs leading-5">Pesapal is EduKit’s planned payment provider, but merchant setup is still pending. You can review this invoice; no payment has been requested or collected. We’ll enable online payment here once checkout is configured.</p>
                </div>
            @else
                <p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs font-semibold leading-5 text-amber-900">{{ $shoppingList->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? ($shoppingList->payment_provider === 'demo' ? 'Demo payment recorded. No money was collected.' : 'Payment received.') : 'Payment opens when this invoice is ready for payment.' }}</p>
            @endif
        </aside>
    </section>
@endsection
