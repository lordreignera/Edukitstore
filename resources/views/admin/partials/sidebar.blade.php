@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'icon' => 'home', 'permission' => 'admin.dashboard.view'],
        ['label' => 'Products', 'route' => 'admin.products.index', 'pattern' => 'admin.products.*', 'icon' => 'products', 'permission' => 'products.view'],
        ['label' => 'Inventory', 'route' => 'admin.inventory.index', 'pattern' => 'admin.inventory.*', 'icon' => 'warehouse', 'permission' => 'inventory.view'],
        ['label' => 'Profit Report', 'route' => 'admin.reports.profit', 'pattern' => 'admin.reports.*', 'icon' => 'chart', 'permission' => 'reports.profit.view'],
        ['label' => 'Categories', 'route' => 'admin.product-categories.index', 'pattern' => 'admin.product-categories.*', 'icon' => 'tag', 'permission' => 'categories.view'],
        ['label' => 'Schools', 'route' => 'admin.schools.index', 'pattern' => 'admin.schools.*', 'icon' => 'school', 'permission' => 'schools.view'],
        ['label' => 'Shopping Lists', 'route' => 'admin.shopping-lists.index', 'pattern' => 'admin.shopping-lists.*', 'icon' => 'list', 'permission' => 'shopping-lists.view'],
        ['label' => 'Invoices', 'route' => 'admin.invoices.index', 'pattern' => 'admin.invoices.*', 'icon' => 'invoice', 'permission' => 'invoices.view'],
        ['label' => 'Suppliers', 'route' => 'admin.suppliers.index', 'pattern' => 'admin.suppliers.*', 'icon' => 'suppliers', 'permission' => 'suppliers.view'],
        ['label' => 'Supplier Products', 'route' => 'admin.supplier-offers.index', 'pattern' => 'admin.supplier-offers.*', 'icon' => 'products', 'permission' => 'supplier-products.view'],
        ['label' => 'Drivers', 'route' => 'admin.drivers.index', 'pattern' => 'admin.drivers.*', 'icon' => 'drivers', 'permission' => 'drivers.view'],
    ];
@endphp

<aside class="fixed inset-y-0 left-0 z-50 flex w-[248px] -translate-x-full flex-col border-r border-orange-500/30 bg-[#071d4f] text-white shadow-2xl transition-transform duration-200 lg:translate-x-0"
    :class="adminMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
    <div class="relative flex h-[80px] items-center justify-center border-b border-orange-200 border-t-4 border-t-orange-600 bg-white px-5">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-center">
            <x-edukit-logo class="h-14 w-14 rounded-full object-contain" />
        </a>
        <button type="button" class="absolute right-3 top-1/2 grid size-10 -translate-y-1/2 place-items-center text-[#071d4f] lg:hidden" aria-label="Close navigation" @click="adminMenuOpen = false">
            <x-ui.icon name="close" />
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-6">
        <p class="px-3 text-[11px] font-bold uppercase text-orange-200/80">Platform management</p>
        <nav class="mt-3 space-y-1" aria-label="Admin navigation">
            @foreach ($navigation as $item)
                @can($item['permission'])
                    <a href="{{ route($item['route']) }}"
                        class="group flex min-h-11 items-center gap-3 rounded-md border px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($item['pattern']) ? 'border-orange-300 bg-white text-[#071d4f] shadow-sm' : 'border-transparent text-slate-100 hover:border-orange-500/50 hover:bg-orange-500/10 hover:text-white' }}">
                        <x-ui.icon :name="$item['icon']" size="size-[18px]" />
                        <span>{{ $item['label'] }}</span>
                        @if ($item['label'] === 'Shopping Lists' && $adminNavStats['shopping_lists'] > 0)
                            <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-950">{{ $adminNavStats['shopping_lists'] }}</span>
                        @elseif ($item['label'] === 'Invoices' && $adminNavStats['invoices'] > 0)
                            <span class="ml-auto rounded-full bg-orange-400 px-2 py-0.5 text-[10px] font-bold text-orange-950">{{ $adminNavStats['invoices'] }}</span>
                        @elseif ($item['label'] === 'Inventory' && $adminNavStats['low_stock'] > 0)
                            <span class="ml-auto rounded-full bg-red-400 px-2 py-0.5 text-[10px] font-bold text-red-950">{{ $adminNavStats['low_stock'] }}</span>
                        @endif
                    </a>
                @endcan
            @endforeach
        </nav>

        @canany(['users.view', 'roles.view', 'permissions.view'])
            <details class="group mt-8">
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-md px-3 text-[11px] font-bold uppercase text-orange-200/80 outline-none transition hover:text-orange-100 focus-visible:ring-2 focus-visible:ring-orange-300 [&::-webkit-details-marker]:hidden">
                    <span>User management</span>
                    <x-ui.icon name="chevron-down" size="size-4" class="transition-transform duration-200 group-open:rotate-180" />
                </summary>
                <nav class="mt-3 space-y-1 border-l border-orange-500/30 pl-3" aria-label="User management navigation">
                    @can('users.view')
                        <a href="{{ route('admin.users.index') }}" class="group flex min-h-10 items-center gap-3 rounded-md border px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.users.*') ? 'border-orange-300 bg-white text-[#071d4f] shadow-sm' : 'border-transparent text-slate-100 hover:border-orange-500/50 hover:bg-orange-500/10 hover:text-white' }}">
                            <x-ui.icon name="user" size="size-[17px]" /><span>Users</span>
                        </a>
                    @endcan
                    @can('roles.view')
                        <a href="{{ route('admin.roles.index') }}" class="group flex min-h-10 items-center gap-3 rounded-md border px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.roles.*') ? 'border-orange-300 bg-white text-[#071d4f] shadow-sm' : 'border-transparent text-slate-100 hover:border-orange-500/50 hover:bg-orange-500/10 hover:text-white' }}">
                            <x-ui.icon name="lock" size="size-[17px]" /><span>Roles</span>
                        </a>
                    @endcan
                    @can('permissions.view')
                        <a href="{{ route('admin.permissions.index') }}" class="group flex min-h-10 items-center gap-3 rounded-md border px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.permissions.*') ? 'border-orange-300 bg-white text-[#071d4f] shadow-sm' : 'border-transparent text-slate-100 hover:border-orange-500/50 hover:bg-orange-500/10 hover:text-white' }}">
                            <x-ui.icon name="key" size="size-[17px]" /><span>Permissions</span>
                        </a>
                    @endcan
                </nav>
            </details>
        @endcanany

        <p class="mt-8 px-3 text-[11px] font-bold uppercase text-orange-200/80">Public store</p>
        <a href="{{ route('website.home') }}" target="_blank" class="mt-3 flex min-h-11 items-center gap-3 rounded-md border border-transparent px-3 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-orange-500/50 hover:bg-orange-500/10 hover:text-white">
            <x-ui.icon name="website" size="size-[18px]" />
            <span>View Website</span>
            <x-ui.icon name="external" size="size-4" class="ml-auto opacity-70" />
        </a>
    </div>

    <div class="border-t border-orange-500/30 bg-[#05163d] p-4">
        <div class="flex items-center gap-3">
            <div class="grid size-10 shrink-0 place-items-center rounded-full border border-orange-200 bg-orange-400 text-sm font-extrabold text-[#071d4f]">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-white">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-orange-200">{{ str(auth()->user()->getRoleNames()->first() ?? 'Administrator')->replace('-', ' ')->title() }}</p>
            </div>
        </div>
    </div>
</aside>
