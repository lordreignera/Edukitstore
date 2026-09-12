<section class="grid gap-4 lg:grid-cols-3">
    <article class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h3 class="text-sm font-extrabold text-slate-950">Recent Shopping Lists</h3><a href="{{ route('admin.shopping-lists.index') }}" class="text-xs font-bold text-blue-700">Review</a></div>
        <div class="divide-y divide-slate-100">
            @forelse ($latestShoppingLists->take(4) as $shoppingList)
                <a href="{{ route('admin.shopping-lists.show', $shoppingList) }}" class="flex items-center gap-3 px-5 py-3.5 hover:bg-slate-50">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-violet-50 text-violet-700"><x-ui.icon name="list" size="size-4" /></span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-sm font-bold text-slate-900">{{ $shoppingList->parent_name }}</span><span class="block truncate text-xs text-slate-500">{{ $shoppingList->school_name ?? 'School pending' }}</span></span>
                    <span class="text-[11px] font-bold {{ $shoppingList->status === 'pending' ? 'text-amber-700' : 'text-emerald-700' }}">{{ ucfirst($shoppingList->status) }}</span>
                </a>
            @empty
                <p class="px-5 py-8 text-sm text-slate-500">No shopping lists yet.</p>
            @endforelse
        </div>
    </article>

    <article class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h3 class="text-sm font-extrabold text-slate-950">Recent Suppliers</h3><a href="{{ route('admin.suppliers.index') }}" class="text-xs font-bold text-blue-700">Manage</a></div>
        <div class="divide-y divide-slate-100">
            @forelse ($latestSuppliers->take(4) as $supplier)
                <div class="flex items-center gap-3 px-5 py-3.5">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-700"><x-ui.icon name="suppliers" size="size-4" /></span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-sm font-bold text-slate-900">{{ $supplier->business_name }}</span><span class="block truncate text-xs text-slate-500">{{ $supplier->district ?? 'District pending' }}</span></span>
                    <span class="text-[11px] font-bold {{ $supplier->is_approved ? 'text-emerald-700' : 'text-amber-700' }}">{{ $supplier->is_approved ? 'Approved' : 'Pending' }}</span>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-slate-500">No suppliers yet.</p>
            @endforelse
        </div>
    </article>

    <article class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h3 class="text-sm font-extrabold text-slate-950">Delivery Partners</h3><a href="{{ route('admin.drivers.index') }}" class="text-xs font-bold text-blue-700">Manage</a></div>
        <div class="divide-y divide-slate-100">
            @forelse ($latestDrivers->take(4) as $driver)
                <div class="flex items-center gap-3 px-5 py-3.5">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-blue-50 text-blue-700"><x-ui.icon name="drivers" size="size-4" /></span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-sm font-bold text-slate-900">{{ $driver->name }}</span><span class="block truncate text-xs text-slate-500">{{ $driver->vehicle_type ?? 'Vehicle pending' }}</span></span>
                    <span class="text-[11px] font-bold {{ $driver->is_approved ? 'text-emerald-700' : 'text-amber-700' }}">{{ $driver->is_approved ? 'Approved' : 'Pending' }}</span>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-slate-500">No drivers yet.</p>
            @endforelse
        </div>
    </article>
</section>
