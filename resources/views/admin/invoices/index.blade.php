<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Invoices</h2>
            <p class="text-sm text-gray-500">Track request approval, invoice release, payment, driver assignment and delivery completion.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('admin.invoices.index') }}" class="mb-6 grid gap-3 border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1.4fr_repeat(5,minmax(0,1fr))_auto]">
                <input name="q" value="{{ $search }}" placeholder="Search reference, phone, name or school" class="rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                <select name="status" class="rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">Any invoice status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="payment_status" class="rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">Any payment</option>
                    @foreach ($paymentStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedPaymentStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="delivery_status" class="rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">Any delivery</option>
                    @foreach ($deliveryStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedDeliveryStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="driver_id" class="rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">Any driver</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}" @selected((string) $driver->id === $selectedDriverId)>{{ $driver->name }}</option>
                    @endforeach
                </select>
                <select name="source" class="rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">Any source</option>
                    <option value="cart" @selected($selectedSource === 'cart')>Cart</option>
                    <option value="upload" @selected($selectedSource === 'upload')>Upload</option>
                </select>
                <button class="rounded bg-[#07215f] px-5 py-2 text-sm font-bold text-white hover:bg-emerald-700">Filter</button>
            </form>

            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Invoice</th>
                                <th class="px-5 py-3">Customer</th>
                                <th class="px-5 py-3">Driver</th>
                                <th class="px-5 py-3">Statuses</th>
                                <th class="px-5 py-3">Total</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-950">{{ $invoice->reference }}</p>
                                        <p class="text-xs text-gray-500">{{ $invoice->source === 'cart' ? 'Cart request' : 'Uploaded list' }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-950">{{ $invoice->parent_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $invoice->phone }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $invoice->assignedDriver?->name ?? '-' }}</td>
                                    <td class="px-5 py-4">
                                        @include('admin.invoices.partials.status-badges', ['invoice' => $invoice])
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-gray-900">{{ $invoice->estimated_total ? 'UGX '.number_format($invoice->estimated_total) : 'Pending' }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-500">No invoices found.</td>
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
