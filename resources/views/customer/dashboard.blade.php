@extends('website.layout')

@section('title', 'My Orders - EduKit')

@section('content')
    <section class="border-b border-[#dbe8f3] bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-8 sm:px-6 lg:px-8">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-orange-700">Customer account</p>
                <h1 class="mt-2 text-3xl font-black text-[#07215f]">My orders</h1>
                <p class="mt-1 text-sm text-slate-600">Welcome, {{ $customer->name }}. Your orders and delivery updates are saved here.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <div class="flex flex-col items-start gap-1">
                    <x-push-notifications />
                    <span class="text-xs text-slate-500">Opt in for order updates and new-product alerts.</span>
                </div>
                <a href="{{ route('website.products.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-orange-600 px-4 py-2 text-sm font-black text-white hover:bg-orange-700">Shop products</a>
                <a href="{{ route('website.upload-list') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-orange-300 bg-white px-4 py-2 text-sm font-bold text-orange-800 hover:bg-orange-50">Upload a school list</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:border-orange-400 hover:text-orange-800">Sign out</button>
                </form>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div role="status" class="rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-900">{{ session('status') }}</div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['All orders', $stats['total']], ['Quotes to pay', $stats['awaiting_payment']], ['Being fulfilled', $stats['in_progress']], ['Completed', $stats['completed']]] as [$label, $value])
                <article class="rounded-md border border-[#dbe8f3] border-t-2 border-t-orange-500 bg-white p-4 shadow-sm">
                    <p class="text-sm font-semibold text-slate-600">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-black text-[#07215f]">{{ number_format($value) }}</p>
                </article>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-lg font-black text-[#07215f]">Order history</h2>
                    <p class="mt-1 text-sm text-slate-600">Open an order to review its quote, payment and delivery timeline.</p>
                </div>
                <p class="text-xs font-semibold text-slate-500">Signed in as {{ $customer->email }}</p>
            </div>

            @forelse ($orders as $order)
                @php($deliveryStatus = $order->deliveryStatus())
                <article class="grid gap-4 border-b border-slate-100 px-5 py-5 last:border-b-0 md:grid-cols-[1fr_auto] md:items-center">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-black text-[#07215f]">{{ $order->reference }}</h3>
                            <span class="rounded-full bg-orange-50 px-2.5 py-1 text-xs font-bold text-orange-800">{{ \App\Models\ShoppingList::statuses()[$order->status] ?? ucfirst($order->status) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ $order->source === \App\Models\ShoppingList::SOURCE_CART ? 'Shopping cart order' : 'Uploaded school list' }} · {{ $order->created_at->format('d M Y') }}</p>
                        <p class="mt-1 text-sm text-slate-600">Payment: {{ $order->payment_provider === 'demo' ? 'Demo paid (no money collected)' : (\App\Models\ShoppingList::paymentStatuses()[$order->payment_status] ?? ucfirst($order->payment_status)) }} <span class="px-1 text-slate-300">|</span> Delivery: {{ \App\Models\ShoppingList::deliveryStatuses()[$deliveryStatus] ?? ucfirst(str_replace('_', ' ', $deliveryStatus)) }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-900">{{ $order->estimated_total ? 'UGX '.number_format($order->estimated_total) : 'Quote being prepared' }}</p>
                    </div>
                    <a href="{{ route('website.quote.show', $order->reference) }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-orange-300 px-4 py-2 text-sm font-bold text-orange-800 hover:bg-orange-50">View order</a>
                </article>
            @empty
                <div class="px-5 py-12 text-center">
                    <h3 class="text-lg font-black text-[#07215f]">Your order history will appear here</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-600">When you shop or upload a school list while signed in, you can return here to review quotes and follow delivery.</p>
                    <a href="{{ route('website.products.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-md bg-orange-600 px-5 py-2.5 text-sm font-black text-white hover:bg-orange-700">Browse products</a>
                </div>
            @endforelse

            @if ($orders->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $orders->links() }}</div>
            @endif
        </section>
    </section>
@endsection
