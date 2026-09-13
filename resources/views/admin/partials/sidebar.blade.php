@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'icon' => 'home'],
        ['label' => 'Products', 'route' => 'admin.products.index', 'pattern' => 'admin.products.*', 'icon' => 'products'],
        ['label' => 'Categories', 'route' => 'admin.product-categories.index', 'pattern' => 'admin.product-categories.*', 'icon' => 'tag'],
        ['label' => 'Schools', 'route' => 'admin.schools.index', 'pattern' => 'admin.schools.*', 'icon' => 'school'],
        ['label' => 'Shopping Lists', 'route' => 'admin.shopping-lists.index', 'pattern' => 'admin.shopping-lists.*', 'icon' => 'list'],
        ['label' => 'Invoices', 'route' => 'admin.invoices.index', 'pattern' => 'admin.invoices.*', 'icon' => 'invoice'],
        ['label' => 'Suppliers', 'route' => 'admin.suppliers.index', 'pattern' => 'admin.suppliers.*', 'icon' => 'suppliers'],
        ['label' => 'Drivers', 'route' => 'admin.drivers.index', 'pattern' => 'admin.drivers.*', 'icon' => 'drivers'],
        ['label' => 'Users & Roles', 'route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'icon' => 'user'],
    ];
@endphp

<aside class="fixed inset-y-0 left-0 z-50 flex w-[248px] -translate-x-full flex-col bg-[#071d4f] text-white shadow-2xl transition-transform duration-200 lg:translate-x-0"
    :class="adminMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
    <div class="flex h-[72px] items-center justify-between border-b border-white/10 bg-white px-5">
        <a href="{{ route('admin.dashboard') }}" class="block min-w-0">
            <img src="{{ asset('images/website/edukit-store-logo.png') }}" alt="EduKit Store" class="h-11 w-auto max-w-[176px] object-contain">
        </a>
        <button type="button" class="grid size-10 place-items-center text-[#071d4f] lg:hidden" aria-label="Close navigation" @click="adminMenuOpen = false">
            <x-ui.icon name="close" />
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-6">
        <p class="px-3 text-[11px] font-bold uppercase text-blue-200/70">Platform management</p>
        <nav class="mt-3 space-y-1" aria-label="Admin navigation">
            @foreach ($navigation as $item)
                <a href="{{ route($item['route']) }}"
                    class="group flex min-h-11 items-center gap-3 rounded-md px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($item['pattern']) ? 'bg-white text-[#071d4f] shadow-sm' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}">
                    <x-ui.icon :name="$item['icon']" size="size-[18px]" />
                    <span>{{ $item['label'] }}</span>
                    @if ($item['label'] === 'Shopping Lists' && $adminNavStats['shopping_lists'] > 0)
                        <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-950">{{ $adminNavStats['shopping_lists'] }}</span>
                    @elseif ($item['label'] === 'Invoices' && $adminNavStats['invoices'] > 0)
                        <span class="ml-auto rounded-full bg-emerald-400 px-2 py-0.5 text-[10px] font-bold text-emerald-950">{{ $adminNavStats['invoices'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <p class="mt-8 px-3 text-[11px] font-bold uppercase text-blue-200/70">Public store</p>
        <a href="{{ route('website.home') }}" target="_blank" class="mt-3 flex min-h-11 items-center gap-3 rounded-md px-3 py-2.5 text-sm font-semibold text-blue-50 transition hover:bg-white/10 hover:text-white">
            <x-ui.icon name="website" size="size-[18px]" />
            <span>View Website</span>
            <x-ui.icon name="external" size="size-4" class="ml-auto opacity-70" />
        </a>
    </div>

    <div class="border-t border-white/10 bg-[#05163d] p-4">
        <div class="flex items-center gap-3">
            <div class="grid size-10 shrink-0 place-items-center rounded-full bg-emerald-400 text-sm font-extrabold text-[#071d4f]">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-white">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-blue-200">{{ str(auth()->user()->getRoleNames()->first() ?? 'Administrator')->replace('-', ' ')->title() }}</p>
            </div>
        </div>
    </div>
</aside>
