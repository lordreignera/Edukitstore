<nav class="flex flex-wrap gap-2 border-b border-slate-200 pb-3" aria-label="Reports">
    @can('reports.profit.view')
        <a href="{{ route('admin.reports.profit') }}" class="rounded-md px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.reports.profit') ? 'bg-orange-100 text-orange-900' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Profit</a>
        <a href="{{ route('admin.reports.delivery-fees') }}" class="rounded-md px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.reports.delivery-fees') ? 'bg-orange-100 text-orange-900' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Delivery fees</a>
    @endcan
    @can('reports.performance.view')
        <a href="{{ route('admin.reports.driver-performance') }}" class="rounded-md px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.reports.driver-performance*') ? 'bg-orange-100 text-orange-900' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Driver performance</a>
        <a href="{{ route('admin.reports.supplier-performance') }}" class="rounded-md px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.reports.supplier-performance*') ? 'bg-orange-100 text-orange-900' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Supplier performance</a>
    @endcan
    @can('reports.driver-payouts.view')
        <a href="{{ route('admin.reports.driver-payouts') }}" class="rounded-md px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.reports.driver-payouts*') ? 'bg-orange-100 text-orange-900' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Driver payouts</a>
    @endcan
</nav>
