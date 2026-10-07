@extends('website.layout')

@section('title', 'Track Invoice - EduKit')

@section('content')
    <section class="bg-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
            <div>
                <p class="text-[11px] font-extrabold uppercase text-emerald-700">Track invoice</p>
                <h1 class="mt-3 text-[36px] font-extrabold leading-tight text-[#07215f]">Check if your EduKit invoice is ready.</h1>
                <p class="mt-4 max-w-xl text-sm leading-7 text-slate-600">Use your order number from the payment receipt, plus the phone number or email used on the order.</p>
            </div>

            <form method="POST" action="{{ route('website.track-order.lookup') }}" class="rounded-md border border-[#dbe8f3] bg-[#f8fbff] p-5 shadow-sm sm:p-7">
                @csrf
                @if ($errors->any())
                    <div class="mb-5 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
                @endif

                <div>
                    <label for="reference" class="text-sm font-bold text-slate-700">Order number</label>
                    <input id="reference" name="reference" value="{{ old('reference') }}" placeholder="EDK-260912-1000" required class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm uppercase focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="mt-4">
                    <label for="contact" class="text-sm font-bold text-slate-700">Phone number or email</label>
                    <input id="contact" name="contact" value="{{ old('contact') }}" placeholder="0700123456 or name@example.com" required class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <button class="mt-6 flex w-full justify-center rounded-md bg-[#07215f] px-5 py-3 text-sm font-extrabold text-white hover:bg-emerald-700">Open invoice</button>
            </form>

            <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 p-5 shadow-sm sm:p-7">
                <p class="text-xs font-black uppercase tracking-wide text-emerald-800">Forgot your order number?</p>
                <h2 class="mt-1 text-xl font-black text-[#07215f]">Find all your orders</h2>
                <p class="mt-2 text-sm leading-6 text-emerald-950">Enter the same phone number or email used at checkout. If you have placed more than one order, we will list them separately so you can choose the right one.</p>
                <form method="POST" action="{{ route('website.track-order.recover') }}" class="mt-4">
                    @csrf
                    <label for="recovery-contact" class="text-sm font-bold text-emerald-950">Phone number or email</label>
                    <input id="recovery-contact" name="contact" value="{{ old('contact', $recoveryContact) }}" placeholder="0700123456 or name@example.com" required class="mt-1 w-full rounded-md border-emerald-200 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <button class="mt-4 flex w-full justify-center rounded-md bg-emerald-700 px-5 py-3 text-sm font-extrabold text-white hover:bg-emerald-800">Find my orders</button>
                </form>
            </div>

            @if ($matches->isNotEmpty())
                <div class="mt-5 rounded-md border border-[#dbe8f3] bg-white p-5 shadow-sm sm:p-7">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-emerald-700">Matching orders</p>
                            <h2 class="mt-1 text-xl font-black text-[#07215f]">Choose an order to track</h2>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $matches->count() }} {{ str('order')->plural($matches->count()) }}</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach ($matches as $match)
                            @php($itemCount = $match->line_items_count ?: count($match->cart_items ?? []))
                            <a href="{{ route('website.quote.show', $match->reference) }}" class="block rounded-md border border-slate-200 p-4 transition hover:border-emerald-500 hover:bg-emerald-50">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black text-[#07215f]">{{ $match->reference }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $match->created_at?->format('d M Y, H:i') }} · {{ $itemCount }} {{ str('item')->plural($itemCount) }}</p>
                                    </div>
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ \App\Models\ShoppingList::deliveryStatuses()[$match->deliveryStatus()] ?? ucfirst($match->deliveryStatus()) }}</span>
                                </div>
                                <div class="mt-3 flex flex-wrap justify-between gap-2 text-sm">
                                    <span class="text-slate-600">{{ $match->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? 'Paid' : ucfirst($match->payment_status) }}</span>
                                    <span class="font-black text-slate-950">{{ $match->estimated_total ? 'UGX '.number_format($match->estimated_total) : 'Total pending' }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <p class="mt-4 text-xs leading-5 text-slate-500">Only orders matching the contact details you entered are shown.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
