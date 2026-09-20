<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['label' => 'Approved products', 'value' => number_format($stats['products']), 'icon' => 'products', 'style' => 'bg-blue-50 text-blue-700'],
        ['label' => 'Units submitted', 'value' => number_format($stats['units_supplied']), 'icon' => 'upload', 'style' => 'bg-violet-50 text-violet-700'],
        ['label' => 'Supplier stock available', 'value' => number_format($stats['units_remaining']), 'icon' => 'warehouse', 'style' => 'bg-amber-50 text-amber-700'],
        ['label' => 'Supplier earnings', 'value' => 'UGX '.number_format($stats['purchase_value']), 'icon' => 'invoice', 'style' => 'bg-emerald-50 text-emerald-700'],
    ] as $stat)
        <section class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold text-slate-500">{{ $stat['label'] }}</p><p class="mt-2 text-xl font-extrabold text-[#071d4f]">{{ $stat['value'] }}</p></div><span class="grid size-10 place-items-center rounded-md {{ $stat['style'] }}"><x-ui.icon :name="$stat['icon']" size="size-5" /></span></div>
        </section>
    @endforeach
</div>
