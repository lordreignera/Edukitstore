<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Inventory summary">
    <article class="rounded-md border border-blue-100 bg-blue-50 p-4">
        <p class="text-xs font-bold text-slate-600">Website/display stock</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['display_units']) }}</p>
        <p class="mt-2 text-xs font-semibold text-blue-700">Current EduKit count; supplier stock is separate</p>
    </article>
    <article class="rounded-md border border-cyan-100 bg-cyan-50 p-4">
        <p class="text-xs font-bold text-slate-600">Warehouse stock</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['warehouse_units']) }}</p>
        <p class="mt-2 text-xs font-semibold text-cyan-700">Current snapshot across all products</p>
    </article>
    <article class="rounded-md border border-orange-200 bg-orange-50 p-4">
        <p class="text-xs font-bold text-slate-600">Stock value</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">UGX {{ number_format($stats['stock_value']) }}</p>
        <p class="mt-2 text-xs font-semibold text-orange-800">Costed batch stock remaining, all products</p>
    </article>
    <article class="rounded-md border border-orange-100 bg-orange-50 p-4">
        <p class="text-xs font-bold text-slate-600">Gross profit after write-offs</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">UGX {{ number_format($stats['gross_profit']) }}</p>
        <p class="mt-2 text-xs font-semibold text-orange-700">Sales margin less unavailable-stock cost for selected dates</p>
    </article>
    <article class="rounded-md border border-slate-200 bg-white p-4">
        <p class="text-xs font-bold text-slate-600">Opening balance</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['period_opening_units']) }}</p>
        <p class="mt-2 text-xs font-semibold text-slate-600">Units before selected start date</p>
    </article>
    <article class="rounded-md border border-slate-200 bg-white p-4">
        <p class="text-xs font-bold text-slate-600">Closing balance</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['period_closing_units']) }}</p>
        <p class="mt-2 text-xs font-semibold text-slate-600">Units by selected end date</p>
    </article>
    <article class="rounded-md border border-amber-100 bg-amber-50 p-4">
        <p class="text-xs font-bold text-slate-600">Low display items</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['low_display_products']) }}</p>
        <p class="mt-2 text-xs font-semibold text-amber-700">Current product counts across all products</p>
    </article>
    <article class="rounded-md border border-orange-100 bg-orange-50 p-4">
        <p class="text-xs font-bold text-slate-600">Sold units</p>
        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['sold_units']) }}</p>
        <p class="mt-2 text-xs font-semibold text-orange-700">Paid EduKit sales for selected dates</p>
    </article>
</section>
