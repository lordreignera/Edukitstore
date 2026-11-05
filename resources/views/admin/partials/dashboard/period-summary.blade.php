<section class="overflow-hidden rounded-lg border border-orange-200 bg-white shadow-sm" aria-labelledby="period-summary-title">
    <div class="flex flex-col gap-4 border-b border-orange-100 bg-orange-50/70 px-5 py-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 id="period-summary-title" class="text-lg font-extrabold text-[#071d4f]">Performance for period</h2>
            <p class="mt-1 text-sm text-slate-600">Order intake uses the order date; payment and profit figures use the payment date.</p>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="grid gap-3 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end">
            <div>
                <label for="dashboard-period" class="mb-1 block text-xs font-bold text-slate-700">Period</label>
                <select id="dashboard-period" name="period" class="h-10 w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600 lg:w-40">
                    <option value="week" @selected($period === 'week')>This week</option>
                    <option value="month" @selected($period === 'month')>This month</option>
                    <option value="year" @selected($period === 'year')>This year</option>
                    <option value="custom" @selected($period === 'custom')>Custom dates</option>
                </select>
            </div>
            <div>
                <label for="dashboard-from" class="mb-1 block text-xs font-bold text-slate-700">From</label>
                <input id="dashboard-from" name="from" type="date" value="{{ $periodFrom }}" class="h-10 w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
            </div>
            <div>
                <label for="dashboard-to" class="mb-1 block text-xs font-bold text-slate-700">To</label>
                <input id="dashboard-to" name="to" type="date" value="{{ $periodTo }}" class="h-10 w-full rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
            </div>
            <label class="flex items-center gap-2 pb-2 text-xs font-semibold text-slate-700">
                <input type="checkbox" name="include_demo" value="1" @checked($includeDemo) class="rounded border-orange-300 text-orange-600 focus:ring-orange-500">
                Include demo payments
            </label>
            <button type="submit" class="h-10 rounded-md bg-orange-600 px-5 text-sm font-bold text-white transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Apply</button>
        </form>
    </div>

    @if ($errors->has('from') || $errors->has('to'))
        <div class="border-b border-red-100 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first('from') ?: $errors->first('to') }}
        </div>
    @endif

    <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-md border border-slate-200 bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Orders received</p>
            <p class="mt-2 text-2xl font-extrabold text-[#071d4f]">{{ number_format($performance['orders_received']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Created {{ strtolower($periodLabel) }}</p>
        </article>
        <article class="rounded-md border border-slate-200 bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Paid orders</p>
            <p class="mt-2 text-2xl font-extrabold text-[#071d4f]">{{ number_format($performance['paid_orders']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Paid {{ strtolower($periodLabel) }}</p>
        </article>
        <article class="rounded-md border border-slate-200 bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Paid item revenue</p>
            <p class="mt-2 text-2xl font-extrabold text-[#071d4f]">UGX {{ number_format($performance['item_revenue']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Excludes delivery and convenience fees</p>
        </article>
        <article class="rounded-md border border-emerald-200 bg-emerald-50/60 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">Gross profit</p>
                    <p class="mt-2 text-2xl font-extrabold text-emerald-900">UGX {{ number_format($performance['gross_profit']) }}</p>
                </div>
                <x-ui.icon name="chart" size="size-5" class="text-emerald-700" />
            </div>
            <p class="mt-1 text-xs text-emerald-800">Realized on paid order items</p>
        </article>
    </div>

    <div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
        <p>{{ $periodFrom }} – {{ $periodTo }}{{ $includeDemo ? ' · Demo/test payments included' : ' · Demo/test payments excluded' }}</p>
        @can('reports.profit.view')
            <a href="{{ route('admin.reports.profit', ['from' => $periodFrom, 'to' => $periodTo] + ($includeDemo ? ['include_demo' => 1] : [])) }}" class="font-bold text-orange-700 hover:text-orange-800">View detailed profit report &rarr;</a>
        @endcan
    </div>
</section>

<script>
    document.querySelectorAll('#dashboard-from, #dashboard-to').forEach((input) => {
        input.addEventListener('change', () => {
            document.getElementById('dashboard-period').value = 'custom';
        });
    });
</script>
