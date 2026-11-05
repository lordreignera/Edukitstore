@php
    $visibleActionQueues = collect($actionQueues)
        ->filter(fn ($queue) => auth()->user()->can($queue['permission']) && $queue['count'] > 0)
        ->values();
@endphp

@canany(['invoices.view', 'shopping-lists.view', 'suppliers.view', 'drivers.view', 'inventory.view'])
    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" aria-labelledby="action-center-title">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center">
            <div>
                <h2 id="action-center-title" class="text-base font-extrabold text-[#071d4f]">Action center</h2>
                <p class="mt-0.5 text-xs text-slate-500">Live queues that need review or a next step.</p>
            </div>
            @can('invoices.view')
                <a href="{{ route('admin.invoices.index') }}" class="text-sm font-bold text-orange-700 hover:text-orange-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">All orders <span aria-hidden="true">→</span></a>
            @endcan
        </div>

        @if ($visibleActionQueues->isNotEmpty())
            <div class="grid gap-x-6 px-4 sm:grid-cols-2">
                @foreach ($visibleActionQueues as $queue)
                    <a href="{{ $queue['route'] }}" class="group flex min-h-[68px] items-center gap-3 border-b border-slate-100 py-3 last:border-b-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-orange-500">
                        <span class="grid size-9 shrink-0 place-items-center rounded-md bg-orange-50 text-orange-700"><x-ui.icon name="chevron-right" size="size-4" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-bold text-slate-800 group-hover:text-orange-800">{{ $queue['label'] }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500">{{ $queue['description'] }}</span>
                        </span>
                        <span class="min-w-9 rounded-full bg-orange-50 px-2.5 py-1 text-center text-sm font-extrabold tabular-nums text-orange-800">{{ number_format($queue['count']) }}</span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="flex items-start gap-3 px-4 py-5">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-700"><x-ui.icon name="check" size="size-4" /></span>
                <div>
                    <p class="text-sm font-bold text-slate-800">Nothing needs attention right now</p>
                    <p class="mt-0.5 text-xs text-slate-500">New requests, approvals, payment reviews and fulfilment tasks will appear here when they need action.</p>
                </div>
            </div>
        @endif
    </section>
@endcanany
