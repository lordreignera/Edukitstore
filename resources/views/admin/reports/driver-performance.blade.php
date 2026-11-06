<x-admin-layout>
    <x-slot name="title">Driver Performance</x-slot>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wide text-orange-700">Monitoring &amp; analytics</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Driver performance</h1>
                <p class="mt-1 text-sm text-slate-500">Completed paid deliveries and manually recorded payouts for the selected delivery period.</p>
            </div>
            <a href="{{ route('admin.reports.driver-performance.export', array_filter(['from' => $from?->toDateString(), 'to' => $to?->toDateString(), 'include_demo' => $includeDemo ? 1 : null], fn ($value) => $value !== null && $value !== '')) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-bold text-orange-900 hover:bg-orange-100"><x-ui.icon name="download" size="size-4" /> Export CSV</a>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            @include('admin.reports.partials.partner-tabs')

            <form method="GET" class="filter-bar" aria-label="Filter driver performance">
                <div><label for="driver-performance-from" class="mb-1 block text-xs font-bold text-slate-700">Completed from</label><input id="driver-performance-from" type="date" name="from" value="{{ $from?->toDateString() }}" class="w-full"></div>
                <div><label for="driver-performance-to" class="mb-1 block text-xs font-bold text-slate-700">Completed to</label><input id="driver-performance-to" type="date" name="to" value="{{ $to?->toDateString() }}" class="w-full"></div>
                <label class="text-sm font-semibold text-slate-700"><input type="checkbox" name="include_demo" value="1" @checked($includeDemo) class="rounded border-orange-300 text-orange-600 focus:ring-orange-500"> Include demo payments</label>
                <button type="submit">Apply</button>
                <a href="{{ route('admin.reports.driver-performance') }}">Clear</a>
            </form>

            @if ($includeDemo)
                <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-950">Demo deliveries are included in performance totals. Demo orders can never be recorded as driver payouts.</div>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Driver performance totals">
                <article class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Completed deliveries</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">{{ number_format($totals['completed']) }}</p><p class="mt-1 text-xs text-slate-500">Assigned deliveries confirmed complete</p></article>
                <article class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Customer fees collected</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">UGX {{ number_format($totals['customer_fees']) }}</p><p class="mt-1 text-xs text-slate-500">Not the driver wage or payout amount</p></article>
                <article class="rounded-lg border border-orange-200 bg-orange-50 p-5"><p class="text-xs font-bold uppercase tracking-wide text-orange-800">Manual payouts recorded</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">UGX {{ number_format($totals['payouts_recorded']) }}</p><p class="mt-1 text-xs text-slate-600">Amounts admins recorded after external payment</p></article>
                <a href="{{ route('admin.reports.driver-payouts', array_filter(['from' => $from?->toDateString(), 'to' => $to?->toDateString()], fn ($value) => $value !== null && $value !== '')) }}" class="rounded-lg border border-orange-300 bg-white p-5 transition hover:bg-orange-50"><p class="text-xs font-bold uppercase tracking-wide text-orange-800">Deliveries awaiting payout record</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">{{ number_format($totals['awaiting_payout']) }}</p><p class="mt-1 text-xs font-semibold text-orange-800">Open driver payout queue →</p></a>
            </section>

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white" aria-labelledby="driver-performance-title">
                <div class="border-b border-slate-100 px-5 py-4"><h2 id="driver-performance-title" class="font-extrabold text-[#071d4f]">Performance by driver</h2><p class="mt-1 text-xs text-slate-500">Payout totals reflect recorded external transfers for orders completed in this date range.</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Driver</th><th class="px-4 py-3">Mobile Money</th><th class="px-4 py-3">Completed</th><th class="px-4 py-3">Customer fees</th><th class="px-4 py-3">Payouts recorded</th><th class="px-4 py-3">Awaiting record</th><th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                <tr class="transition-colors hover:bg-orange-50/30">
                                    <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-900">{{ $row->assignedDriver?->name ?? 'Driver account unavailable' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $row->assignedDriver?->payment_phone ?: 'Not set' }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ number_format($row->completed_deliveries) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">UGX {{ number_format($row->customer_fees_collected) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-bold text-orange-800">UGX {{ number_format($row->recorded_payouts) }}</td>
                                    <td class="px-4 py-3">{{ number_format($row->awaiting_payout_count) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right"><a href="{{ route('admin.reports.driver-payouts', array_filter(['driver_id' => $row->assigned_driver_id, 'from' => $from?->toDateString(), 'to' => $to?->toDateString()], fn ($value) => $value !== null && $value !== '')) }}" class="font-bold text-orange-800 hover:underline">Payouts</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">No completed paid deliveries match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <p class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-950">Customer delivery/convenience fees are reported separately from driver payouts. The payout figure is only the manual amount recorded for a completed order after an external Mobile Money transfer.</p>
        </div>
    </div>
</x-admin-layout>
