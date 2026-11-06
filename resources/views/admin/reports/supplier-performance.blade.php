<x-admin-layout>
    <x-slot name="title">Supplier Performance</x-slot>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wide text-orange-700">Monitoring &amp; analytics</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Supplier performance</h1>
                <p class="mt-1 text-sm text-slate-500">Paid supplier-fulfilled order results and settlement status for the selected payment period.</p>
            </div>
            <a href="{{ route('admin.reports.supplier-performance.export', array_filter(['from' => $from?->toDateString(), 'to' => $to?->toDateString(), 'include_demo' => $includeDemo ? 1 : null], fn ($value) => $value !== null && $value !== '')) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-bold text-orange-900 hover:bg-orange-100"><x-ui.icon name="download" size="size-4" /> Export CSV</a>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            @include('admin.reports.partials.partner-tabs')

            <form method="GET" class="filter-bar" aria-label="Filter supplier performance">
                <div><label for="supplier-performance-from" class="mb-1 block text-xs font-bold text-slate-700">Paid from</label><input id="supplier-performance-from" type="date" name="from" value="{{ $from?->toDateString() }}" class="w-full"></div>
                <div><label for="supplier-performance-to" class="mb-1 block text-xs font-bold text-slate-700">Paid to</label><input id="supplier-performance-to" type="date" name="to" value="{{ $to?->toDateString() }}" class="w-full"></div>
                <label class="text-sm font-semibold text-slate-700"><input type="checkbox" name="include_demo" value="1" @checked($includeDemo) class="rounded border-orange-300 text-orange-600 focus:ring-orange-500"> Include demo payments</label>
                <button type="submit">Apply</button>
                <a href="{{ route('admin.reports.supplier-performance') }}">Clear</a>
            </form>

            @if ($includeDemo)
                <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-950">Demo supplier orders are included for dry-run analysis. These totals are not real collections or supplier settlements.</div>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Supplier performance totals">
                <article class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Paid orders</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">{{ number_format($totals->paid_orders ?? 0) }}</p><p class="mt-1 text-xs text-slate-500">Orders with supplier-fulfilled items</p></article>
                <article class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Units sold</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">{{ number_format($totals->units_sold ?? 0) }}</p><p class="mt-1 text-xs text-slate-500">Across matching paid orders</p></article>
                <article class="rounded-lg border border-orange-200 bg-orange-50 p-5"><p class="text-xs font-bold uppercase tracking-wide text-orange-800">Supplier payable</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">UGX {{ number_format($totals->supplier_payable ?? 0) }}</p><p class="mt-1 text-xs text-slate-600">Reserved plus earned amounts</p></article>
                <article class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">EduKit margin</p><p class="mt-2 text-2xl font-extrabold text-[#071d4f]">UGX {{ number_format($totals->edukit_margin ?? 0) }}</p><p class="mt-1 text-xs text-slate-500">Gross margin on supplier items</p></article>
            </section>

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white" aria-labelledby="supplier-performance-title">
                <div class="border-b border-slate-100 px-5 py-4"><h2 id="supplier-performance-title" class="font-extrabold text-[#071d4f]">Results by supplier</h2><p class="mt-1 text-xs text-slate-500">“Earned” means the order has been confirmed delivered; it does not mean a supplier transfer has been recorded.</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Supplier</th><th class="px-4 py-3">Paid orders</th><th class="px-4 py-3">Units</th><th class="px-4 py-3">Sales</th><th class="px-4 py-3">Supplier payable</th><th class="px-4 py-3">Earned</th><th class="px-4 py-3">Reserved</th><th class="px-4 py-3">EduKit margin</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                <tr class="transition-colors hover:bg-orange-50/30">
                                    <td class="px-4 py-3 font-bold text-slate-900">{{ $row->supplier?->business_name ?? 'Supplier account unavailable' }}</td>
                                    <td class="px-4 py-3">{{ number_format($row->paid_orders) }}</td>
                                    <td class="px-4 py-3">{{ number_format($row->units_sold) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">UGX {{ number_format($row->sales) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-bold">UGX {{ number_format($row->supplier_payable) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-orange-800">UGX {{ number_format($row->earned) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-amber-800">UGX {{ number_format($row->reserved) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-bold text-orange-800">UGX {{ number_format($row->edukit_margin) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">No paid supplier sales match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
