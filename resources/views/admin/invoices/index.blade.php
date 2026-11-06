<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Orders &amp; Fulfilment</h2>
            <p class="text-sm text-gray-500">Manage cart orders and uploaded school lists from payment through driver assignment and confirmed delivery.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-5 border-l-4 border-orange-500 bg-orange-50 px-4 py-3 text-sm leading-6 text-orange-950">Open an order to review its invoice, monitor payment, resolve stock issues, and assign an approved available driver. Drivers can start delivery after payment is verified.</div>
            <div class="mb-3 flex justify-end">
                <a href="{{ route('admin.invoices.export', array_filter([
                    'q' => $search !== '' ? $search : null,
                    'status' => $selectedStatus !== '' ? $selectedStatus : null,
                    'payment_status' => $selectedPaymentStatus !== '' ? $selectedPaymentStatus : null,
                    'delivery_status' => $selectedDeliveryStatus !== '' ? $selectedDeliveryStatus : null,
                    'driver_id' => $selectedDriverId !== '' ? $selectedDriverId : null,
                    'source' => $selectedSource !== '' ? $selectedSource : null,
                    'issues' => $issuesOnly ? 1 : null,
                    'date_field' => $dateField !== '' ? $dateField : null,
                    'from' => $dateFrom !== '' ? $dateFrom : null,
                    'to' => $dateTo !== '' ? $dateTo : null,
                    'exclude_demo' => $excludeDemo ? 1 : null,
                ], fn ($value) => $value !== null && $value !== '')) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-bold text-orange-800 transition hover:border-orange-500 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <x-ui.icon name="download" size="size-4" /> Export filtered orders CSV
                </a>
            </div>
            <form method="GET" action="{{ route('admin.invoices.index') }}" class="filter-bar mb-6">
                <input name="q" value="{{ $search }}" placeholder="Search order, phone, name or school" class="rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                <select name="status" class="rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="">Any order status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="payment_status" class="rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="">Any payment</option>
                    @foreach ($paymentStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedPaymentStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="delivery_status" class="rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="">Any delivery</option>
                    @foreach ($deliveryStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedDeliveryStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="driver_id" class="rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="">Any driver</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}" @selected((string) $driver->id === $selectedDriverId)>{{ $driver->name }}</option>
                    @endforeach
                </select>
                <select name="source" class="rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="">Any source</option>
                    <option value="cart" @selected($selectedSource === 'cart')>Cart</option>
                    <option value="upload" @selected($selectedSource === 'upload')>Upload</option>
                </select>
                <label class="flex items-center gap-2 text-xs font-bold text-red-700"><input type="checkbox" name="issues" value="1" @checked($issuesOnly) class="rounded border-red-300">Payment issues</label>
                <div class="grid gap-2 sm:grid-cols-2 xl:col-span-4 xl:grid-cols-4">
                    <label class="block text-xs font-semibold text-slate-600">Date is
                        <select name="date_field" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                            <option value="" @selected($dateField === '')>Any date</option>
                            <option value="created" @selected($dateField === 'created')>Order received</option>
                            <option value="paid" @selected($dateField === 'paid')>Payment date</option>
                        </select>
                    </label>
                    <label class="block text-xs font-semibold text-slate-600">From
                        <input type="date" name="from" value="{{ $dateFrom }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    </label>
                    <label class="block text-xs font-semibold text-slate-600">To
                        <input type="date" name="to" value="{{ $dateTo }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                    </label>
                    <div class="flex items-end justify-between gap-3">
                        <label class="flex items-center gap-2 pb-2 text-xs font-semibold text-slate-600"><input type="checkbox" name="exclude_demo" value="1" @checked($excludeDemo) class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">Exclude demo payments</label>
                        <button class="rounded-md bg-[#07215f] px-5 py-2 text-sm font-bold text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Filter</button>
                    </div>
                </div>
            </form>

            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Order</th>
                                <th class="px-5 py-3">Customer</th>
                                <th class="px-5 py-3">Driver</th>
                                <th class="px-5 py-3">Statuses</th>
                                <th class="px-5 py-3">Total</th>
                                <th class="px-5 py-3">EduKit profit</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-950">{{ $invoice->reference }}</p>
                                        <p class="text-xs text-gray-500">{{ $invoice->source === 'cart' ? 'Cart request' : 'Uploaded list' }}</p>
                                        @if ($invoice->payment_exception)<p class="mt-1 text-xs font-bold text-red-700">Payment needs review</p>@endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-950">{{ $invoice->parent_name }}</p>
                                        <p class="text-xs text-gray-500">MoMo: {{ $invoice->phone }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $invoice->school?->name ?? $invoice->school_name ?? 'School pending' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $invoice->assignedDriver?->name ?? '-' }}</td>
                                    <td class="px-5 py-4">
                                        @include('admin.invoices.partials.status-badges', ['invoice' => $invoice])
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-gray-900">{{ $invoice->estimated_total ? 'UGX '.number_format($invoice->estimated_total) : 'Pending' }}</td>
                                    <td class="px-5 py-4">
                                        @if ($invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID && $invoice->payment_exception)
                                            <span class="text-xs font-bold text-amber-700">Pending stock review</span>
                                        @elseif ($invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID && $invoice->realized_profit !== null)
                                            <span class="font-extrabold text-emerald-700">UGX {{ number_format($invoice->realized_profit) }}</span>
                                            @if ($invoice->payment_provider === 'demo')<span class="mt-1 block text-[10px] font-bold uppercase text-amber-700">Test profit</span>@endif
                                        @elseif ($invoice->payment_status === \App\Models\ShoppingList::PAYMENT_PAID)
                                            <span class="text-xs font-semibold text-slate-500">Awaiting allocation</span>
                                        @else
                                            <span class="text-xs text-slate-400">After payment</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="rounded bg-orange-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-800">Open order</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-gray-500">No orders match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $invoices->links() }}
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
