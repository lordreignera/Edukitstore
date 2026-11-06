<x-admin-layout>
    <x-slot name="title">Driver Payouts</x-slot>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wide text-orange-700">Monitoring &amp; analytics</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Driver payouts</h1>
                <p class="mt-1 text-sm text-slate-500">Record Mobile Money payments already sent for completed deliveries.</p>
            </div>
            <a href="{{ route('admin.reports.driver-payouts.export', array_filter(['from' => $from?->toDateString(), 'to' => $to?->toDateString(), 'driver_id' => $driverId], fn ($value) => $value !== null && $value !== '')) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-bold text-orange-900 hover:bg-orange-100"><x-ui.icon name="download" size="size-4" /> Export payout register</a>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            @include('admin.reports.partials.partner-tabs')

            @if (session('status'))
                <div role="status" class="rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-900">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-950">
                This page records a transfer after an administrator has sent it outside EduKit. EduKit does not initiate Mobile Money payments. Enter the exact amount sent and the provider transaction reference; customer delivery fees are not used as a wage formula.
            </div>

            <form method="GET" class="filter-bar" aria-label="Filter driver payout records">
                <div><label for="payouts-from" class="mb-1 block text-xs font-bold text-slate-700">Delivery completed from</label><input id="payouts-from" type="date" name="from" value="{{ $from?->toDateString() }}" class="w-full"></div>
                <div><label for="payouts-to" class="mb-1 block text-xs font-bold text-slate-700">Delivery completed to</label><input id="payouts-to" type="date" name="to" value="{{ $to?->toDateString() }}" class="w-full"></div>
                <div><label for="payouts-driver" class="mb-1 block text-xs font-bold text-slate-700">Driver</label><select id="payouts-driver" name="driver_id" class="w-full"><option value="">All drivers</option>@foreach ($drivers as $driver)<option value="{{ $driver->id }}" @selected((string) $driverId === (string) $driver->id)>{{ $driver->name }}</option>@endforeach</select></div>
                <button type="submit">Apply</button>
                <a href="{{ route('admin.reports.driver-payouts') }}">Clear</a>
            </form>

            <section class="grid gap-4 sm:grid-cols-3" aria-label="Driver payout totals">
                <article class="rounded-lg border border-orange-200 bg-orange-50 p-5"><p class="text-xs font-bold uppercase tracking-wide text-orange-800">Completed deliveries to review</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">{{ number_format($totals['eligible']) }}</p><p class="mt-1 text-xs text-slate-600">Real paid orders with no payout record</p></article>
                <article class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Payouts recorded</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">{{ number_format($totals['recorded_count']) }}</p><p class="mt-1 text-xs text-slate-500">For deliveries completed in this filter</p></article>
                <article class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Amount recorded</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">UGX {{ number_format($totals['recorded_amount']) }}</p><p class="mt-1 text-xs text-slate-500">Manual payment entries; not an automatic wage calculation</p></article>
            </section>

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white" aria-labelledby="eligible-payouts-title">
                <div class="border-b border-slate-100 px-5 py-4"><h2 id="eligible-payouts-title" class="font-extrabold text-[#071d4f]">Completed deliveries awaiting a payout record</h2><p class="mt-1 text-xs text-slate-500">Only non-demo, paid, completed orders without payment exceptions are eligible.</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Completed</th><th class="px-4 py-3">Order / customer</th><th class="px-4 py-3">Driver</th><th class="px-4 py-3">Payment number</th><th class="px-4 py-3">Customer fee</th><th class="px-4 py-3"><span class="sr-only">Action</span></th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($eligibleOrders as $order)
                                @php($completedAt = $order->customer_received_at ?: $order->delivery_confirmed_at)
                                <tr class="transition-colors hover:bg-orange-50/30">
                                    <td class="whitespace-nowrap px-4 py-3">{{ $completedAt?->format('d M Y, H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3"><a href="{{ route('admin.invoices.show', $order) }}" class="font-bold text-orange-800 hover:underline">{{ $order->reference }}</a><span class="block text-xs text-slate-500">{{ $order->parent_name }}</span></td>
                                    <td class="px-4 py-3 font-semibold">{{ $order->assignedDriver?->name ?? 'Driver unavailable' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $order->assignedDriver?->payment_phone ?: 'Not set' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">UGX {{ number_format($order->delivery_fee ?? 0) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        @can('reports.driver-payouts.record')
                                            @if ($order->assignedDriver?->payment_phone)
                                                <button type="button" @click="$dispatch('open-admin-modal', 'record-driver-payout-{{ $order->id }}')" class="inline-flex min-h-9 items-center rounded-md bg-orange-700 px-3 py-2 text-xs font-bold text-white hover:bg-orange-800">Record payment</button>
                                            @else
                                                <span class="text-xs font-semibold text-amber-800">Add driver payment number</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No eligible completed deliveries match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">{{ $eligibleOrders->links() }}</div>
            </section>

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white" aria-labelledby="payout-history-title">
                <div class="border-b border-slate-100 px-5 py-4"><h2 id="payout-history-title" class="font-extrabold text-[#071d4f]">Recorded payout history</h2><p class="mt-1 text-xs text-slate-500">Each delivery can only have one payout record. Entries retain the payment number, transaction reference and recording administrator.</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Paid at</th><th class="px-4 py-3">Order</th><th class="px-4 py-3">Driver</th><th class="px-4 py-3">Payment number</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Transaction reference</th><th class="px-4 py-3">Recorded by</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($payouts as $payout)
                                <tr><td class="whitespace-nowrap px-4 py-3">{{ $payout->paid_at?->format('d M Y, H:i') }}</td><td class="px-4 py-3 font-bold">{{ $payout->shoppingList?->reference ?? 'Order unavailable' }}</td><td class="px-4 py-3">{{ $payout->driver?->name ?? 'Driver unavailable' }}</td><td class="whitespace-nowrap px-4 py-3">{{ $payout->payment_phone }}</td><td class="whitespace-nowrap px-4 py-3 font-extrabold text-orange-800">UGX {{ number_format($payout->amount) }}</td><td class="px-4 py-3 font-mono text-xs">{{ $payout->transaction_reference }}</td><td class="px-4 py-3">{{ $payout->recorder?->name ?? 'Former administrator' }}</td></tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">No driver payouts have been recorded for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">{{ $payouts->links() }}</div>
            </section>
        </div>
    </div>

    @push('modals')
        @can('reports.driver-payouts.record')
        @foreach ($eligibleOrders as $order)
            @if ($order->assignedDriver?->payment_phone)
                <x-admin-modal name="record-driver-payout-{{ $order->id }}" title="Record driver payment" description="Confirm the external transfer has already been sent before recording it here.">
                    <div class="mb-4 rounded-md border border-slate-200 bg-slate-50 p-4 text-sm">
                        <p class="font-bold text-[#071d4f]">{{ $order->reference }} · {{ $order->assignedDriver->name }}</p>
                        <p class="mt-1 text-slate-600">Mobile Money number: <strong>{{ $order->assignedDriver->payment_phone }}</strong></p>
                        <p class="mt-1 text-slate-600">Customer fee: UGX {{ number_format($order->delivery_fee ?? 0) }} <span class="text-slate-500">(informational only; not the payout amount)</span></p>
                    </div>
                    <form method="POST" action="{{ route('admin.reports.driver-payouts.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="shopping_list_id" value="{{ $order->id }}">
                        <input type="hidden" name="_payout_modal" value="record-driver-payout-{{ $order->id }}">
                        <input type="hidden" name="from" value="{{ $from?->toDateString() }}">
                        <input type="hidden" name="to" value="{{ $to?->toDateString() }}">
                        <input type="hidden" name="driver_id" value="{{ $driverId }}">
                        <div><label for="payout-amount-{{ $order->id }}" class="block text-sm font-bold text-slate-700">Amount sent (UGX)</label><input id="payout-amount-{{ $order->id }}" name="amount" type="number" min="1" max="1000000000" step="1" value="{{ old('amount') }}" required class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600"><p class="mt-1 text-xs text-slate-500">Enter the amount that was actually transferred for this delivery.</p></div>
                        <div><label for="payout-reference-{{ $order->id }}" class="block text-sm font-bold text-slate-700">Mobile Money transaction reference</label><input id="payout-reference-{{ $order->id }}" name="transaction_reference" value="{{ old('transaction_reference') }}" maxlength="120" required class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600"></div>
                        <div><label for="payout-paid-at-{{ $order->id }}" class="block text-sm font-bold text-slate-700">Transfer date and time</label><input id="payout-paid-at-{{ $order->id }}" name="paid_at" type="datetime-local" max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600"></div>
                        <div><label for="payout-notes-{{ $order->id }}" class="block text-sm font-bold text-slate-700">Note (optional)</label><textarea id="payout-notes-{{ $order->id }}" name="notes" rows="2" maxlength="1000" class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">{{ old('notes') }}</textarea></div>
                        <div class="flex justify-end"><button type="submit" class="inline-flex min-h-10 items-center rounded-md bg-orange-700 px-4 py-2 text-sm font-bold text-white hover:bg-orange-800">Confirm payout record</button></div>
                    </form>
                </x-admin-modal>
            @endif
        @endforeach

        @if ($errors->any() && old('_payout_modal'))
            <div x-data x-init="$nextTick(() => $dispatch('open-admin-modal', @js(old('_payout_modal'))))"></div>
        @endif
        @endcan
    @endpush
</x-admin-layout>
