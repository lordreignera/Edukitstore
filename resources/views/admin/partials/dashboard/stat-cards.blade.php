@php
    $cards = [
        ['label' => 'Total Products', 'value' => $stats['products'], 'note' => number_format($stats['active_products']).' active on website', 'icon' => 'products', 'tone' => 'border-blue-100 bg-blue-50 text-blue-700'],
        ['label' => 'Shopping Lists', 'value' => $stats['shopping_lists'], 'note' => number_format($stats['pending_shopping_lists']).' awaiting review', 'icon' => 'list', 'tone' => 'border-violet-100 bg-violet-50 text-violet-700'],
        ['label' => 'Approved Suppliers', 'value' => $stats['approved_suppliers'], 'note' => number_format($stats['pending_suppliers']).' pending approval', 'icon' => 'suppliers', 'tone' => 'border-emerald-100 bg-emerald-50 text-emerald-700'],
        ['label' => 'Approved Drivers', 'value' => $stats['approved_drivers'], 'note' => number_format($stats['pending_drivers']).' pending approval', 'icon' => 'drivers', 'tone' => 'border-amber-100 bg-amber-50 text-amber-700'],
    ];
@endphp

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Platform summary">
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
</section>
