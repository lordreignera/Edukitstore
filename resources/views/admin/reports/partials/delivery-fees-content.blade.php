<form method="GET" action="{{ $financeView ? route('finance.delivery-fees') : route('admin.reports.delivery-fees') }}" class="grid gap-3 rounded-md border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
    <div>
        <label for="delivery-fees-from" class="mb-1 block text-xs font-bold text-slate-700">Completed from</label>
        <input id="delivery-fees-from" type="date" name="from" value="{{ $from?->format('Y-m-d') }}" class="h-10 w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
    </div>
    <div>
        <label for="delivery-fees-to" class="mb-1 block text-xs font-bold text-slate-700">Completed to</label>
        <input id="delivery-fees-to" type="date" name="to" value="{{ $to?->format('Y-m-d') }}" class="h-10 w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
    </div>
    @if (! $financeView)
        <label class="flex items-center gap-2 self-end pb-2 text-sm font-semibold text-amber-900">
            <input type="checkbox" name="include_demo" value="1" @checked($includeDemo) class="rounded border-amber-400 text-orange-700 focus:ring-orange-600">
            Include demo/test payments
        </label>
    @endif
    <button type="submit" class="h-10 self-end rounded-md bg-orange-600 px-4 text-sm font-bold text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Apply filters</button>
</form>

@if ($errors->any())
    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert">{{ $errors->first() }}</div>
@endif

@if ($includeDemo)
    <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-950">Dry-run view: demo payments are included. These orders did not collect real money.</div>
@endif

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Completed delivery fee totals">
    <article class="rounded-md border border-orange-200 bg-orange-50 p-5">
        <p class="text-xs font-bold uppercase tracking-wide text-orange-800">Customer fees collected</p>
        <p class="mt-2 text-2xl font-extrabold text-[#071d4f]">UGX {{ number_format($totals['fees_collected']) }}</p>
        <p class="mt-1 text-xs text-slate-600">From paid, completed orders in this report</p>
    </article>
    <article class="rounded-md border border-slate-200 bg-white p-5">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Completed paid orders</p>
        <p class="mt-2 text-2xl font-extrabold text-[#071d4f]">{{ number_format($totals['completed_orders']) }}</p>
        <p class="mt-1 text-xs text-slate-500">Completion is confirmed receipt or delivery confirmation</p>
    </article>
    <article class="rounded-md border border-slate-200 bg-white p-5">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Orders without a driver</p>
        <p class="mt-2 text-2xl font-extrabold text-[#071d4f]">{{ number_format($totals['orders_without_driver']) }}</p>
        <p class="mt-1 text-xs text-slate-500">Includes pickup orders and records without an assigned driver</p>
    </article>
</section>

<div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-950">
    This report shows the customer delivery/convenience fee recorded on each completed order, grouped by assigned driver for reconciliation. Some orders may include supplier delivery charges in that amount; it is not a driver wage or payout calculation. Driver pay rates and payouts are not yet recorded in EduKit.
</div>

<section class="overflow-hidden rounded-md border border-slate-200 bg-white" aria-labelledby="driver-fees-title">
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 id="driver-fees-title" class="font-extrabold text-[#071d4f]">Fees by driver</h2>
        <p class="mt-1 text-xs text-slate-500">Customer fees across completed orders attributed to each driver.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3">Driver</th><th class="px-4 py-3">Payment number</th><th class="px-4 py-3">Completed orders</th><th class="px-4 py-3">Fees collected</th><th class="px-4 py-3">Average fee / order</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($driverTotals as $driverTotal)
                    @php($driver = $driverTotal->assignedDriver)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $driver?->name ?? 'No driver assigned' }}</td>
                        <td class="px-4 py-3">{{ $driver?->payment_phone ?: '—' }}</td>
                        <td class="px-4 py-3">{{ number_format($driverTotal->completed_orders) }}</td>
                        <td class="px-4 py-3 font-extrabold text-orange-700">UGX {{ number_format($driverTotal->fees_collected) }}</td>
                        <td class="px-4 py-3">UGX {{ number_format($driverTotal->completed_orders ? $driverTotal->fees_collected / $driverTotal->completed_orders : 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No completed paid orders match these dates.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="overflow-hidden rounded-md border border-slate-200 bg-white" aria-labelledby="completed-orders-title">
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 id="completed-orders-title" class="font-extrabold text-[#071d4f]">Completed orders</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3">Completed</th><th class="px-4 py-3">Order</th><th class="px-4 py-3">School / destination</th><th class="px-4 py-3">Driver</th><th class="px-4 py-3">Customer fee collected</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    @php($completedAt = $order->customer_received_at ?: $order->delivery_confirmed_at)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">{{ $completedAt?->format('d M Y, H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $order->reference }}@if ($order->payment_provider === 'demo')<span class="mt-1 block text-[10px] font-extrabold uppercase text-amber-700">Test payment</span>@endif</td>
                        <td class="px-4 py-3">{{ $order->school?->name ?? $order->school_name ?? ucfirst($order->delivery_preference) }}</td>
                        <td class="px-4 py-3">{{ $order->assignedDriver?->name ?? 'No driver assigned' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-extrabold text-orange-700">{{ $order->delivery_fee === null ? 'Not recorded' : 'UGX '.number_format($order->delivery_fee) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No completed paid orders match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-4 py-3">{{ $orders->links() }}</div>
</section>
