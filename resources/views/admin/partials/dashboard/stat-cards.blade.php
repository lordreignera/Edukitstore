@php
    $cards = [
        ['label' => 'Total Products', 'value' => $stats['products'], 'note' => number_format($stats['active_products']).' active on website', 'icon' => 'products', 'tone' => 'border-blue-100 bg-blue-50 text-blue-700'],
        ['label' => 'Display Stock', 'value' => $stats['display_stock'], 'note' => number_format($stats['warehouse_stock']).' warehouse, '.number_format($stats['low_display_stock']).' low', 'icon' => 'warehouse', 'tone' => 'border-sky-100 bg-sky-50 text-sky-700'],
        ['label' => 'Schools', 'value' => $stats['schools'], 'note' => number_format($stats['active_schools']).' visible at checkout', 'icon' => 'school', 'tone' => 'border-cyan-100 bg-cyan-50 text-cyan-700'],
        ['label' => 'Orders & Lists', 'value' => $stats['shopping_lists'], 'note' => number_format($stats['pending_shopping_lists']).' uploaded lists awaiting review', 'icon' => 'invoice', 'tone' => 'border-orange-100 bg-orange-50 text-orange-700'],
        ['label' => 'Approved Suppliers', 'value' => $stats['approved_suppliers'], 'note' => number_format($stats['pending_suppliers']).' pending approval', 'icon' => 'suppliers', 'tone' => 'border-orange-100 bg-orange-50 text-orange-700'],
        ['label' => 'Approved Drivers', 'value' => $stats['approved_drivers'], 'note' => number_format($stats['pending_drivers']).' pending approval', 'icon' => 'drivers', 'tone' => 'border-amber-100 bg-amber-50 text-amber-700'],
    ];
@endphp

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7" aria-label="Platform summary">
    @foreach ($cards as $card)
        <article class="rounded-md border p-4 {{ $card['tone'] }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold text-slate-600">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($card['value']) }}</p>
                </div>
                <span class="grid size-10 shrink-0 place-items-center rounded-md bg-white/80 shadow-sm">
                    <x-ui.icon :name="$card['icon']" size="size-5" />
                </span>
            </div>
            <p class="mt-3 text-xs font-semibold">{{ $card['note'] }}</p>
        </article>
    @endforeach

    @can('invoices.view')
        <a href="{{ route('admin.invoices.index', ['payment_status' => \App\Models\ShoppingList::PAYMENT_PAID, 'delivery_status' => 'awaiting_driver_assignment']) }}" class="rounded-md border border-orange-200 border-l-4 border-l-orange-500 bg-orange-50 p-4 transition hover:border-orange-400 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-orange-500" aria-label="View {{ $stats['orders_awaiting_driver_assignment'] }} paid orders awaiting driver assignment">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold text-slate-700">Paid orders awaiting driver</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['orders_awaiting_driver_assignment']) }}</p>
                </div>
                <span class="grid size-10 shrink-0 place-items-center rounded-md bg-white/80 text-orange-700 shadow-sm">
                    <x-ui.icon name="drivers" size="size-5" />
                </span>
            </div>
            <p class="mt-3 text-xs font-semibold text-orange-900">Open ready-to-assign orders &rarr;</p>
        </a>
    @endcan
</section>
