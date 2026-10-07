<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Invoice {{ $invoice->reference }}</h2>
                <p class="mt-1 text-sm text-gray-500">Monitor payment, assign a driver and follow the journey through customer receipt.</p>
            </div>
            <a href="{{ route('admin.invoices.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back to invoices</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
            <section class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                @if (session('status'))
                    <div class="mb-5 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div>
                        <p class="text-xs font-bold uppercase text-gray-500">{{ $invoice->source === 'cart' ? 'Cart request' : 'Uploaded shopping list' }}</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-950">Customer Details</h3>
                    </div>
                    @include('admin.invoices.partials.status-badges', ['invoice' => $invoice])
                </div>

                @include('admin.invoices.partials.customer-summary', ['invoice' => $invoice])
                @if ($invoice->payment_provider === 'demo')
                    <p class="mt-4 rounded border border-amber-300 bg-amber-50 p-3 text-sm font-bold text-amber-900">Demo payment only. No money was collected.</p>
                @endif
                @include('admin.invoices.partials.items', ['invoice' => $invoice])
                @if ($invoice->payment_exception)
                    <div class="mt-5 rounded border border-red-300 bg-red-50 p-4 text-sm font-bold text-red-800">Payment needs review: {{ $invoice->payment_exception }}</div>
                    @if ($invoice->payment_exception_type === 'stock')
                        <form method="POST" action="{{ route('admin.invoices.resolve-paid-stock', $invoice) }}" class="mt-3">@csrf @method('PATCH')<button class="rounded bg-emerald-700 px-4 py-2 text-sm font-bold text-white">Recheck and allocate stock</button></form>
                    @endif
                @endif
                @if ($invoice->paymentAttempts->isNotEmpty())
                    <div class="mt-5 overflow-x-auto rounded border border-slate-200">
                        <table class="min-w-full text-left text-xs"><thead class="bg-slate-50"><tr><th class="px-3 py-2">Checkout reference</th><th class="px-3 py-2">Provider transaction</th><th class="px-3 py-2">Amount</th><th class="px-3 py-2">State</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($invoice->paymentAttempts as $attempt)<tr><td class="px-3 py-2">{{ $attempt->tx_ref }}</td><td class="px-3 py-2">{{ $attempt->provider_transaction_id ?: '-' }}</td><td class="px-3 py-2">UGX {{ number_format($attempt->amount) }}</td><td class="px-3 py-2 font-bold">{{ str($attempt->status)->replace('_', ' ')->title() }}</td></tr>@endforeach</tbody></table>
                    </div>
                @endif

                @if ($invoice->file_path)
                    <div class="mt-6 border-t border-gray-100 pt-5">
                        <p class="text-sm font-semibold text-gray-500">Uploaded file</p>
                        <a href="{{ route('admin.shopping-lists.download', $invoice) }}" class="mt-2 inline-flex rounded bg-[#07215f] px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                            Download {{ $invoice->original_filename }}
                        </a>
                    </div>
                @endif
            </section>

            <section class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-gray-950">Invoice workflow</h3>
                <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                    <div class="rounded border border-slate-200 bg-slate-50 p-3">
                        <p class="font-bold text-slate-500">Payment</p>
                        <p class="mt-1 font-black text-slate-900">{{ \App\Models\ShoppingList::paymentStatuses()[$invoice->payment_status] ?? ucfirst($invoice->payment_status) }}</p>
                    </div>
                    <div class="rounded border border-slate-200 bg-slate-50 p-3">
                        <p class="font-bold text-slate-500">Driver</p>
                        <p class="mt-1 font-black text-slate-900">{{ $invoice->assignedDriver?->name ?? 'Unassigned' }}</p>
                    </div>
                    <div class="rounded border border-slate-200 bg-slate-50 p-3">
                        <p class="font-bold text-slate-500">Delivery</p>
                        <p class="mt-1 font-black text-slate-900">{{ \App\Models\ShoppingList::deliveryStatuses()[$invoice->deliveryStatus()] }}</p>
                    </div>
                </div>

                @include('admin.invoices.partials.review-form', ['invoice' => $invoice, 'statuses' => $statuses, 'drivers' => $drivers])

                @if ($invoice->driver_started_at)
                    <div class="mt-6 rounded border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900">
                        <p class="font-bold">Driver started the journey</p>
                        <p class="mt-1">{{ $invoice->driver_started_at->format('M d, Y H:i') }}</p>
                    </div>
                @endif
                @if ($invoice->driver_reached_at)
                    <div class="mt-3 rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="font-bold">Driver reached the destination</p>
                        <p class="mt-1">{{ $invoice->driver_reached_at->format('M d, Y H:i') }}</p>
                    </div>
                @endif
                @if ($invoice->customer_received_at || $invoice->delivery_confirmed_at)
                    <div class="mt-6 rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                        <p class="font-bold">Customer confirmed receipt</p>
                        <p class="mt-1">{{ ($invoice->customer_received_at ?: $invoice->delivery_confirmed_at)->format('M d, Y H:i') }}</p>
                        @if ($invoice->customer_received_name)
                            <p class="mt-2">Received by {{ $invoice->customer_received_name }}</p>
                        @endif
                        @if ($invoice->customer_received_notes ?: $invoice->delivery_notes)
                            <p class="mt-2 whitespace-pre-line">{{ $invoice->customer_received_notes ?: $invoice->delivery_notes }}</p>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-admin-layout>
