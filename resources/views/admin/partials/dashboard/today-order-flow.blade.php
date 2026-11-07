@php
    $todayReceivedUrl = route('admin.invoices.index', [
        'date_field' => 'created',
        'from' => $todayFlow['date'],
        'to' => $todayFlow['date'],
    ]);
    $awaitingFulfilmentUrl = route('admin.invoices.index', [
        'payment_status' => \App\Models\ShoppingList::PAYMENT_PAID,
        'delivery_status' => 'awaiting_driver_assignment',
    ]);
    $assignedOrdersUrl = route('admin.invoices.index', ['delivery_status' => 'assigned']);
    $completedTodayUrl = route('admin.invoices.index', [
        'delivery_status' => 'delivered',
        'date_field' => 'completed',
        'from' => $todayFlow['date'],
        'to' => $todayFlow['date'],
    ]);

    $todayCards = [
        [
            'label' => 'Orders received today',
            'value' => $todayFlow['received'],
            'note' => 'New orders created today',
            'action' => "View today's orders",
            'url' => $todayReceivedUrl,
        ],
        [
            'label' => 'Open queue: awaiting fulfilment',
            'value' => $todayFlow['awaiting_fulfilment'],
            'note' => 'All dates · paid orders waiting for packing and driver assignment',
            'action' => 'Open awaiting fulfilment',
            'url' => $awaitingFulfilmentUrl,
        ],
        [
            'label' => 'Open queue: assigned to drivers',
            'value' => $todayFlow['assigned_to_drivers'],
            'note' => 'All dates · packed, paid deliveries not yet completed',
            'action' => 'View assigned orders',
            'url' => $assignedOrdersUrl,
        ],
        [
            'label' => 'Completed today',
            'value' => $todayFlow['completed'],
            'note' => 'Customer receipt confirmed today',
            'action' => 'View completed today',
            'url' => $completedTodayUrl,
        ],
    ];
@endphp

@can('invoices.view')
    <section aria-labelledby="today-order-flow-title" class="space-y-3">
        <div class="flex flex-wrap items-end justify-between gap-2 px-1">
            <div>
                <h2 id="today-order-flow-title" class="text-base font-extrabold text-[#071d4f]">Order flow today</h2>
                <p class="mt-0.5 text-xs text-slate-500">Today's intake and completions, plus the current paid-order queues.</p>
            </div>
            <span class="text-xs font-semibold text-slate-500">{{ $todayFlow['date'] }}</span>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-4">
            @foreach ($todayCards as $card)
                <a href="{{ $card['url'] }}" class="group rounded-lg border border-slate-200 border-t-2 border-t-orange-500 bg-white p-4 shadow-sm transition hover:border-orange-300 hover:shadow focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-2 text-2xl font-extrabold tabular-nums text-[#071d4f]">{{ number_format($card['value']) }}</p>
                        </div>
                        <x-ui.icon name="chevron-right" size="size-4" class="mt-1 shrink-0 text-orange-700 transition group-hover:translate-x-0.5" />
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ $card['note'] }}</p>
                    <span class="mt-3 inline-block text-xs font-bold text-orange-700 group-hover:text-orange-800">{{ $card['action'] }} <span aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </div>
    </section>
@endcan
