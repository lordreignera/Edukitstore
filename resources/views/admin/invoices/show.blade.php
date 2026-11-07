<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Order {{ $invoice->reference }}</h2>
                <p class="mt-1 text-sm text-gray-500">Customer details first, then check availability, pack and assign a driver.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.invoices.print', $invoice) }}" target="_blank" rel="noopener" class="rounded border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-bold text-orange-900 hover:bg-orange-100">Print invoice / fulfilment sheet</a>
                <a href="{{ route('admin.invoices.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back to orders</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-medium text-orange-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $errors->first() }}</div>
            @endif
            <section class="mb-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="customer-details-heading">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">{{ $invoice->source === 'cart' ? 'Cart order' : 'Uploaded shopping list' }} · {{ $invoice->reference }}</p>
                        <h3 id="customer-details-heading" class="mt-1 text-lg font-black text-slate-950">{{ $invoice->parent_name }}</h3>
                    </div>
                    @include('admin.invoices.partials.status-badges', ['invoice' => $invoice])
                </div>
                <dl class="mt-4 grid gap-x-6 gap-y-3 border-t border-slate-100 pt-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-xs font-semibold text-slate-500">Mobile Money phone</dt><dd class="mt-0.5 font-bold text-slate-900">{{ $invoice->phone }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500">Email</dt><dd class="mt-0.5 break-all font-medium text-slate-900">{{ $invoice->email ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500">School</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $invoice->school?->name ?? $invoice->school_name ?? 'Not specified' }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500">Learner / class</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $invoice->learner_name ?: '—' }}{{ $invoice->class_level ? ' · '.$invoice->class_level : '' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold text-slate-500">Delivery destination</dt><dd class="mt-0.5 font-medium text-slate-900">{{ ucfirst($invoice->delivery_preference) }}{{ $invoice->delivery_location ? ' · '.$invoice->delivery_location : '' }}{{ $invoice->school?->district?->name ? ' · '.$invoice->school->district->name : '' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold text-slate-500">Customer note</dt><dd class="mt-0.5 whitespace-pre-line font-medium text-slate-900">{{ $invoice->notes ?: 'No extra notes.' }}</dd></div>
                </dl>
            </section>
            @include('admin.invoices.partials.fulfilment-panel', ['invoice' => $invoice, 'drivers' => $drivers])
        </div>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
            <section class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div>
                        <p class="text-xs font-bold uppercase text-gray-500">{{ $invoice->source === 'cart' ? 'Cart request' : 'Uploaded shopping list' }}</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-950">Payment and order details</h3>
                    </div>
                    @include('admin.invoices.partials.status-badges', ['invoice' => $invoice])
                </div>

                @if ($invoice->payment_provider === 'demo')
                    <p class="mt-4 rounded border border-amber-300 bg-amber-50 p-3 text-sm font-bold text-amber-900">Demo payment only. No money was collected.</p>
                @endif
                @php
                    $profitLines = $invoice->lineItems->filter(fn ($item) => $item->profit_total !== null);
                    $realizedProfit = $profitLines->sum('profit_total');
                    $recordedCost = $profitLines->sum('cost_total');
                @endphp
                @if ($invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID && ! $invoice->payment_exception && $profitLines->isNotEmpty())
                    <section class="mt-5 rounded-md border {{ $invoice->payment_provider === 'demo' ? 'border-amber-300 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-4" aria-label="Order profit summary">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-wide {{ $invoice->payment_provider === 'demo' ? 'text-amber-800' : 'text-emerald-800' }}">
                                    {{ $invoice->payment_provider === 'demo' ? 'Test profit — demo payment' : 'Realized EduKit profit' }}
                                </p>
                                <p class="mt-1 text-2xl font-black {{ $invoice->payment_provider === 'demo' ? 'text-amber-950' : 'text-emerald-950' }}">UGX {{ number_format($realizedProfit) }}</p>
                            </div>
                            <div class="text-right text-xs font-semibold text-slate-600">
                                <p>Item revenue: UGX {{ number_format($profitLines->sum('line_total')) }}</p>
                                <p class="mt-1">Recorded item cost/payable: UGX {{ number_format($recordedCost) }}</p>
                            </div>
                        </div>
                        @if ($invoice->payment_provider === 'demo')
                            <p class="mt-2 text-xs font-semibold text-amber-900">For dry-run review only; no funds were collected and this is excluded from normal financial totals.</p>
                        @endif
                    </section>
                @elseif ($invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID)
                    <p class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Profit will appear after payment is verified and stock costs are allocated.</p>
                @else
                    <p class="mt-4 rounded border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Profit is recorded after payment is verified and item costs are allocated.</p>
                @endif
                @if ($invoice->payment_exception)
                    <div class="mt-5 rounded border border-red-300 bg-red-50 p-4 text-sm font-bold text-red-800">Payment needs review: {{ $invoice->payment_exception }}</div>
                    @if ($invoice->payment_exception_type === 'stock')
                        <p class="mt-2 text-sm text-red-800">Use the product check above to mark only the unavailable quantities. The available quantities will be allocated and packed when you save.</p>
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
                        <a href="{{ route('admin.shopping-lists.download', $invoice) }}" class="mt-2 inline-flex rounded bg-[#07215f] px-4 py-2 text-sm font-bold text-white hover:bg-orange-700">
                            Download {{ $invoice->original_filename }}
                        </a>
                    </div>
                @endif
            </section>

            <section class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-gray-950">Order workflow</h3>
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
                        <p class="mt-1 font-black text-slate-900">{{ $invoice->fulfilmentStatusLabel() }}</p>
                    </div>
                </div>

                @include('admin.invoices.partials.review-form', ['invoice' => $invoice, 'statuses' => $statuses, 'drivers' => $drivers])

                @if ($invoice->driver_started_at)
                    <div class="mt-6 rounded border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900">
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
                    <div class="mt-6 rounded border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900">
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
