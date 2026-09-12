@php
    $pendingTotal = array_sum($adminNavStats);
@endphp

<header class="sticky top-0 z-30 flex min-h-[72px] items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-7">
    <button type="button" class="grid size-10 shrink-0 place-items-center rounded-md border border-slate-200 text-slate-700 lg:hidden" aria-label="Open navigation" @click="adminMenuOpen = true">
        <x-ui.icon name="menu" />
    </button>

    <form method="GET" action="{{ route('admin.products.index') }}" class="relative hidden w-full max-w-md sm:block">
        <label for="admin-search" class="sr-only">Search products</label>
        <x-ui.icon name="search" size="size-[18px]" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
        <input id="admin-search" name="q" value="{{ request('q') }}" placeholder="Search products by name or SKU..." class="h-10 w-full rounded-md border-slate-200 bg-slate-50 pl-10 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-emerald-600">
    </form>

    <div class="ml-auto flex items-center gap-2 sm:gap-3">
        <a href="{{ route('admin.shopping-lists.index') }}" class="relative grid size-10 place-items-center rounded-md text-slate-600 transition hover:bg-slate-100 hover:text-[#071d4f]" aria-label="Open operational alerts">
            <x-ui.icon name="bell" />
            @if ($pendingTotal > 0)
                <span class="absolute right-1.5 top-1.5 size-2 rounded-full bg-red-500 ring-2 ring-white"></span>
            @endif
        </a>

        <div class="relative" x-data="{ userMenuOpen: false }">
            <button type="button" class="flex h-11 items-center gap-2 rounded-md px-2 text-left transition hover:bg-slate-100" @click="userMenuOpen = !userMenuOpen" :aria-expanded="userMenuOpen">
                <span class="grid size-9 place-items-center rounded-full bg-emerald-100 text-xs font-extrabold text-emerald-800">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                <span class="hidden max-w-36 sm:block">
                    <span class="block truncate text-sm font-bold text-slate-900">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-xs text-slate-500">Administrator</span>
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
