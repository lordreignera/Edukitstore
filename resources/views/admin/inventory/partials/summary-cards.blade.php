<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Inventory summary">
    <article class="rounded-md border border-blue-100 bg-blue-50 p-4">
        <p class="text-xs font-bold text-slate-600">Website/display stock</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['display_units']) }}</p>
        <p class="mt-2 text-xs font-semibold text-blue-700">Available for parents to buy</p>
    </article>
    <article class="rounded-md border border-cyan-100 bg-cyan-50 p-4">
        <p class="text-xs font-bold text-slate-600">Warehouse stock</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['warehouse_units']) }}</p>
        <p class="mt-2 text-xs font-semibold text-cyan-700">Back-room quantity</p>
    </article>
    <article class="rounded-md border border-amber-100 bg-amber-50 p-4">
        <p class="text-xs font-bold text-slate-600">Low display items</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['low_display_products']) }}</p>
        <p class="mt-2 text-xs font-semibold text-amber-700">Need transfer or buying</p>
    </article>
    <article class="rounded-md border border-emerald-100 bg-emerald-50 p-4">
        <p class="text-xs font-bold text-slate-600">Sold units</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['sold_units']) }}</p>
        <p class="mt-2 text-xs font-semibold text-emerald-700">From verified cart payments</p>
    </article>
    <article class="rounded-md border border-violet-100 bg-violet-50 p-4">
        <p class="text-xs font-bold text-slate-600">Gross profit</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">UGX {{ number_format($stats['gross_profit']) }}</p>
        <p class="mt-2 text-xs font-semibold text-violet-700">Selling price minus wholesale cost</p>
    </article>
</section>
