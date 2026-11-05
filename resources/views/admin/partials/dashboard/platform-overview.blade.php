@php
    $cards = [
        ['label' => 'Products', 'value' => $stats['products'], 'note' => number_format($stats['active_products']).' active on the website', 'icon' => 'products', 'permission' => 'products.view', 'route' => 'admin.products.index'],
        ['label' => 'Available stock', 'value' => $stats['display_stock'], 'note' => number_format($stats['warehouse_stock']).' in warehouse · '.number_format($stats['low_display_stock']).' at reorder level', 'icon' => 'warehouse', 'permission' => 'inventory.view', 'route' => 'admin.inventory.index'],
        ['label' => 'Schools', 'value' => $stats['schools'], 'note' => number_format($stats['active_schools']).' active at checkout', 'icon' => 'school', 'permission' => 'schools.view', 'route' => 'admin.schools.index'],
        ['label' => 'Orders & lists', 'value' => $stats['shopping_lists'], 'note' => number_format($stats['pending_shopping_lists']).' pending list requests', 'icon' => 'invoice', 'permission' => 'invoices.view', 'route' => 'admin.invoices.index'],
        ['label' => 'Approved suppliers', 'value' => $stats['approved_suppliers'], 'note' => number_format($stats['pending_suppliers']).' awaiting approval', 'icon' => 'suppliers', 'permission' => 'suppliers.view', 'route' => 'admin.suppliers.index'],
        ['label' => 'Approved drivers', 'value' => $stats['approved_drivers'], 'note' => number_format($stats['pending_drivers']).' awaiting approval', 'icon' => 'drivers', 'permission' => 'drivers.view', 'route' => 'admin.drivers.index'],
    ];
@endphp

<section aria-labelledby="platform-overview-title">
    <div class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="platform-overview-title" class="text-base font-extrabold text-[#071d4f]">Platform overview</h2>
        <p class="text-xs text-slate-500">Current snapshots · not affected by the selected period</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        @foreach ($cards as $card)
            @can($card['permission'])
                <a href="{{ route($card['route']) }}" class="group rounded-lg border border-slate-200 bg-white p-3.5 shadow-sm transition hover:border-orange-300 hover:shadow focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-1 text-2xl font-extrabold tabular-nums text-[#071d4f]">{{ number_format($card['value']) }}</p>
                        </div>
                        <span class="grid size-8 shrink-0 place-items-center rounded-md bg-orange-50 text-orange-700"><x-ui.icon :name="$card['icon']" size="size-4" /></span>
                    </div>
                    <p class="mt-1 text-[11px] leading-4 text-slate-500">{{ $card['note'] }}</p>
                </a>
            @endcan
        @endforeach
    </div>
</section>
