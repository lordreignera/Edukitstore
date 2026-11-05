<header class="sticky top-0 z-30 flex min-h-[72px] flex-wrap items-center gap-3 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur print:hidden sm:px-6 lg:px-7">
    <button type="button" class="grid size-10 shrink-0 place-items-center rounded-md border border-orange-200 text-[#071d4f] transition hover:border-orange-400 hover:bg-orange-50 lg:hidden" aria-label="Open navigation" @click="adminMenuOpen = true">
        <x-ui.icon name="menu" />
    </button>

    <form method="GET" action="{{ route('admin.search') }}" x-data="{ searching: false }" @submit="searching = true" class="relative order-3 w-full sm:order-none sm:min-w-0 sm:max-w-xl sm:flex-1">
        <label for="admin-search" class="sr-only">Search orders, products, schools, suppliers and drivers</label>
        <x-ui.icon name="search" size="size-[18px]" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
        <input id="admin-search" name="q" value="{{ request()->routeIs('admin.search') ? request('q') : '' }}" minlength="2" maxlength="100" required placeholder="Search orders, products, schools, suppliers, drivers…" class="h-10 w-full rounded-md border-slate-200 bg-slate-50 pl-10 pr-12 text-sm text-slate-800 placeholder:text-slate-400 focus:border-orange-600 focus:bg-white focus:ring-orange-600">
        <button type="submit" :disabled="searching" class="absolute right-1 top-1 grid size-8 place-items-center rounded text-orange-700 transition hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 disabled:opacity-60" aria-label="Search the platform" title="Search">
            <span x-show="!searching"><x-ui.icon name="search" size="size-4" /></span>
            <span x-cloak x-show="searching" class="text-[10px] font-bold">…</span>
        </button>
    </form>

    <div class="ml-auto flex items-center gap-2 sm:gap-3">
        @can('invoices.view')
            <a href="{{ route('admin.notifications.index') }}" class="relative grid size-10 place-items-center rounded-md text-slate-600 transition hover:bg-orange-50 hover:text-orange-700" aria-label="Open notifications">
                <x-ui.icon name="bell" />
                @if ($unreadNotificationCount > 0)
                    <span class="absolute -right-0.5 -top-0.5 min-w-4 rounded-full bg-red-600 px-1 text-center text-[10px] font-black leading-4 text-white">{{ min($unreadNotificationCount, 99) }}</span>
                @endif
            </a>
        @endcan

        <x-push-notifications />

        <div class="relative" x-data="{ userMenuOpen: false }">
            <button type="button" class="flex h-11 items-center gap-2 rounded-md px-2 text-left transition hover:bg-slate-100" @click="userMenuOpen = !userMenuOpen" :aria-expanded="userMenuOpen">
                <span class="grid size-9 place-items-center rounded-full bg-orange-100 text-xs font-extrabold text-orange-800 ring-1 ring-inset ring-orange-200">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                <span class="hidden max-w-36 sm:block">
                    <span class="block truncate text-sm font-bold text-slate-900">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ str(auth()->user()->getRoleNames()->first() ?? 'Administrator')->replace('-', ' ')->title() }}</span>
                </span>
                <x-ui.icon name="chevron-down" size="size-4" class="hidden text-slate-400 sm:block" />
            </button>

            <div x-cloak x-show="userMenuOpen" @click.outside="userMenuOpen = false" x-transition class="absolute right-0 mt-2 w-52 rounded-md border border-slate-200 bg-white p-1.5 shadow-xl">
                <a href="{{ route('profile.show') }}" class="flex items-center gap-2 rounded px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <x-ui.icon name="user" size="size-4" />
                    Profile settings
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 rounded px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                        <x-ui.icon name="logout" size="size-4" />
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
