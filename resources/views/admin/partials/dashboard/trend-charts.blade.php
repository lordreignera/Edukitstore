@php
    $points = $trend['points'];
    $maxOrders = max(1, max(array_column($points, 'orders')));
    $maxRevenue = max(1, max(array_column($points, 'revenue')));
    $labelStep = max(1, (int) ceil(count($points) / 6));
    $intervalLabel = match ($trend['granularity']) {
        'week' => 'weekly',
        'month' => 'monthly',
        default => 'daily',
    };
@endphp

@canany(['invoices.view', 'reports.profit.view'])
<section class="grid gap-4 xl:grid-cols-2" aria-label="Business trends for {{ $periodFrom }} through {{ $periodTo }}">
    @can('invoices.view')
    <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-extrabold text-[#071d4f]">Orders received</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ ucfirst($intervalLabel) }} order creation · {{ $periodFrom }} – {{ $periodTo }}</p>
            </div>
            <span class="rounded-md bg-orange-50 px-2 py-1 text-[11px] font-bold text-orange-800">{{ number_format($performance['orders_received']) }} total</span>
        </div>

        @if ($performance['orders_received'] > 0)
            <p class="mt-3 text-[11px] text-slate-400 md:hidden">Swipe the chart horizontally to inspect each date.</p>
            <div class="mt-4 overflow-x-auto pb-1" role="group" aria-label="{{ ucfirst($intervalLabel) }} order counts">
                <div class="flex h-32 min-w-[520px] items-end gap-1 border-b border-slate-200 px-1">
                    @foreach ($points as $point)
                        @php($barHeight = $point['orders'] > 0 ? max(3, ($point['orders'] / $maxOrders) * 100) : 0)
                        <div class="flex h-full min-w-0 flex-1 flex-col justify-end">
                            <div role="img" aria-label="{{ $point['label'] }}: {{ number_format($point['orders']) }} orders" title="{{ $point['label'] }}: {{ number_format($point['orders']) }} orders" class="w-full rounded-t-sm bg-orange-500 transition hover:bg-orange-700" style="height: {{ $barHeight }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex min-w-[520px] gap-1 px-1 pt-1" aria-hidden="true">
                    @foreach ($points as $point)
                        <span class="min-w-0 flex-1 truncate text-center text-[10px] text-slate-500">{{ $loop->index % $labelStep === 0 || $loop->last ? $point['label'] : '' }}</span>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-4 grid h-32 place-items-center rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 text-center text-sm text-slate-500">No orders were received during this period.</div>
        @endif

        <details class="mt-2 text-xs text-slate-500">
            <summary class="w-fit cursor-pointer rounded text-orange-700 hover:text-orange-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-orange-600">View chart data</summary>
            <ul class="mt-2 grid gap-x-4 gap-y-1 sm:grid-cols-2">
                @foreach ($points as $point)
                    <li>{{ $point['label'] }}: {{ number_format($point['orders']) }} orders</li>
                @endforeach
            </ul>
        </details>
    </article>
    @endcan

    @can('reports.profit.view')
    <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-extrabold text-[#071d4f]">Paid item revenue</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ ucfirst($intervalLabel) }} paid item sales · excludes delivery and convenience fees</p>
            </div>
            <span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-700">UGX {{ number_format($performance['item_revenue']) }}</span>
        </div>

        @if ($performance['item_revenue'] > 0)
            <p class="mt-3 text-[11px] text-slate-400 md:hidden">Swipe the chart horizontally to inspect each date.</p>
            <div class="mt-4 overflow-x-auto pb-1" role="group" aria-label="{{ ucfirst($intervalLabel) }} paid item revenue">
                <div class="flex h-32 min-w-[520px] items-end gap-1 border-b border-slate-200 px-1">
                    @foreach ($points as $point)
                        @php($barHeight = $point['revenue'] > 0 ? max(3, ($point['revenue'] / $maxRevenue) * 100) : 0)
                        <div class="flex h-full min-w-0 flex-1 flex-col justify-end">
                            <div role="img" aria-label="{{ $point['label'] }}: UGX {{ number_format($point['revenue']) }} paid item revenue" title="{{ $point['label'] }}: UGX {{ number_format($point['revenue']) }}" class="w-full rounded-t-sm bg-[#18366f] transition hover:bg-orange-700" style="height: {{ $barHeight }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex min-w-[520px] gap-1 px-1 pt-1" aria-hidden="true">
                    @foreach ($points as $point)
                        <span class="min-w-0 flex-1 truncate text-center text-[10px] text-slate-500">{{ $loop->index % $labelStep === 0 || $loop->last ? $point['label'] : '' }}</span>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-4 grid h-32 place-items-center rounded-md border border-dashed border-slate-200 bg-slate-50 px-4 text-center text-sm text-slate-500">No paid item revenue was recorded during this period.</div>
        @endif

        <details class="mt-2 text-xs text-slate-500">
            <summary class="w-fit cursor-pointer rounded text-orange-700 hover:text-orange-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-orange-600">View chart data</summary>
            <ul class="mt-2 grid gap-x-4 gap-y-1 sm:grid-cols-2">
                @foreach ($points as $point)
                    <li>{{ $point['label'] }}: UGX {{ number_format($point['revenue']) }}</li>
                @endforeach
            </ul>
        </details>
    </article>
    @endcan
</section>
@endcanany
