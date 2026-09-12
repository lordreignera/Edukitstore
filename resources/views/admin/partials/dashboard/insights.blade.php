@php
    $maxCategoryCount = max(1, (int) $productCategorySummary->max('products_count'));
    $shoppingListTotal = max(1, (int) $shoppingListStatusSummary->sum('count'));
    $statusTones = [
        'pending' => 'bg-amber-500',
        'reviewing' => 'bg-blue-500',
        'quoted' => 'bg-violet-500',
        'fulfilled' => 'bg-emerald-500',
        'cancelled' => 'bg-red-400',
    ];
@endphp

<section class="grid gap-5 xl:grid-cols-[1.45fr_1fr]" aria-label="Catalogue and request insights">
    <article class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-extrabold text-slate-950">Catalogue by Category</h3>
            <p class="mt-0.5 text-xs text-slate-500">Product coverage across the master catalogue</p>
        </div>
        <div class="space-y-4 p-5">
            @forelse ($productCategorySummary as $category)
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-4 text-xs">
                        <span class="truncate font-bold text-slate-700">{{ $category->name }}</span>
                        <span class="shrink-0 font-extrabold text-slate-950">{{ number_format($category->products_count) }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-blue-600" style="width: {{ max(5, round(($category->products_count / $maxCategoryCount) * 100)) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-500">Product categories will appear here once added.</p>
            @endforelse
        </div>
    </article>

    <article class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-extrabold text-slate-950">Shopping List Workflow</h3>
            <p class="mt-0.5 text-xs text-slate-500">Current requests grouped by fulfilment stage</p>
        </div>
        <div class="p-5">
            <div class="flex h-3 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                @foreach ($shoppingListStatusSummary as $status)
                    @if ($status['count'] > 0)
                        <span class="{{ $statusTones[$status['status']] ?? 'bg-slate-400' }}" style="width: {{ ($status['count'] / $shoppingListTotal) * 100 }}%"></span>
                    @endif
                @endforeach
            </div>

            <div class="mt-5 space-y-3">
                @foreach ($shoppingListStatusSummary as $status)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="size-2.5 shrink-0 rounded-full {{ $statusTones[$status['status']] ?? 'bg-slate-400' }}"></span>
                        <span class="flex-1 font-semibold text-slate-600">{{ $status['label'] }}</span>
                        <span class="font-extrabold text-slate-950">{{ number_format($status['count']) }}</span>
                        <span class="w-10 text-right text-xs font-semibold text-slate-400">{{ round(($status['count'] / $shoppingListTotal) * 100) }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    </article>
</section>
