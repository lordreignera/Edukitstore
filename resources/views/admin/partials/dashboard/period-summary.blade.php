@php
    $paidOrdersUrl = route('admin.invoices.index', [
        'payment_status' => \App\Models\ShoppingList::PAYMENT_PAID,
        'date_field' => 'paid',
        'from' => $periodFrom,
        'to' => $periodTo,
    ] + ($includeDemo ? [] : ['exclude_demo' => 1]));
    $profitReportUrl = route('admin.reports.profit', [
        'from' => $periodFrom,
        'to' => $periodTo,
    ] + ($includeDemo ? ['include_demo' => 1] : []));
    $cards = [
        [
            'label' => 'Orders received in period',
            'value' => number_format($performance['orders_received']),
            'note' => 'Created '.strtolower($periodLabel),
            'icon' => 'invoice',
            'permission' => 'invoices.view',
            'url' => route('admin.invoices.index', ['date_field' => 'created', 'from' => $periodFrom, 'to' => $periodTo]),
        ],
        [
            'label' => 'Paid orders in period',
            'value' => number_format($performance['paid_orders']),
            'note' => 'Paid '.strtolower($periodLabel),
            'icon' => 'check',
            'permission' => 'invoices.view',
            'url' => $paidOrdersUrl,
        ],
        [
            'label' => 'Paid item revenue',
            'value' => 'UGX '.number_format($performance['item_revenue']),
            'note' => 'Excludes delivery and convenience fees',
            'icon' => 'invoice',
            'permission' => 'reports.profit.view',
            'url' => $profitReportUrl,
        ],
        [
            'label' => 'Gross profit',
            'value' => 'UGX '.number_format($performance['gross_profit']),
            'note' => 'Realized on paid order items',
            'icon' => 'chart',
            'permission' => 'reports.profit.view',
            'url' => $profitReportUrl,
        ],
    ];
@endphp

<section aria-labelledby="performance-title" class="space-y-3">
    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm xl:flex-row xl:items-center xl:justify-between">
        <div class="min-w-0">
            <h2 id="performance-title" class="text-sm font-extrabold text-[#071d4f]">Performance for selected period</h2>
            <p class="mt-0.5 text-xs text-slate-500">Order counts use creation date; paid and profit figures use payment date. Platform cards below are current snapshots.</p>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" x-data="{ applying: false }" @submit="applying = true" class="filter-bar filter-bar--inline">
            <div>
                <label for="dashboard-period" class="sr-only">Performance period</label>
                <select id="dashboard-period" name="period" class="h-9 w-36 rounded-md border-slate-300 py-1 text-sm focus:border-orange-600 focus:ring-orange-600">
                    <option value="week" @selected($period === 'week')>This week</option>
                    <option value="month" @selected($period === 'month')>This month</option>
                    <option value="year" @selected($period === 'year')>This year</option>
                    <option value="custom" @selected($period === 'custom')>Custom dates</option>
                </select>
            </div>
            <div>
                <label for="dashboard-from" class="sr-only">From date</label>
                <input id="dashboard-from" name="from" type="date" value="{{ $periodFrom }}" aria-label="From date" class="h-9 w-36 rounded-md border-slate-300 py-1 text-sm focus:border-orange-600 focus:ring-orange-600">
            </div>
            <div>
                <label for="dashboard-to" class="sr-only">To date</label>
                <input id="dashboard-to" name="to" type="date" value="{{ $periodTo }}" aria-label="To date" class="h-9 w-36 rounded-md border-slate-300 py-1 text-sm focus:border-orange-600 focus:ring-orange-600">
            </div>
            <label class="flex h-9 items-center gap-2 px-1 text-xs font-semibold text-slate-600">
                <input type="checkbox" name="include_demo" value="1" @checked($includeDemo) class="rounded border-orange-300 text-orange-600 focus:ring-orange-500">
                Include demo
            </label>
            <button type="submit" :disabled="applying" class="h-9 rounded-md bg-orange-600 px-4 text-sm font-bold text-white transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-70"><span x-show="!applying">Apply</span><span x-cloak x-show="applying">Applying…</span></button>
        </form>
    </div>

    @if ($errors->has('from') || $errors->has('to'))
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700" role="alert">
            {{ $errors->first('from') ?: $errors->first('to') }}
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-4">
        @foreach ($cards as $card)
            @can($card['permission'])
                <a href="{{ $card['url'] }}" class="group rounded-lg border border-slate-200 border-t-2 border-t-orange-500 bg-white p-4 shadow-sm transition hover:border-orange-300 hover:shadow focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-2 truncate text-2xl font-extrabold text-[#071d4f]">{{ $card['value'] }}</p>
                        </div>
                        <span class="grid size-9 shrink-0 place-items-center rounded-md bg-orange-50 text-orange-700"><x-ui.icon :name="$card['icon']" size="size-[18px]" /></span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ $card['note'] }}</p>
                    <span class="sr-only">Open filtered {{ strtolower($card['label']) }}</span>
                </a>
            @endcan
        @endforeach
    </div>

    <div class="flex flex-col gap-2 px-1 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
        <p>{{ $periodFrom }} – {{ $periodTo }} · {{ $includeDemo ? 'Demo/test payments included' : 'Demo/test payments excluded' }}</p>
        @can('reports.profit.view')
            <a href="{{ $profitReportUrl }}" class="font-bold text-orange-700 hover:text-orange-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">View detailed profit report <span aria-hidden="true">→</span></a>
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
