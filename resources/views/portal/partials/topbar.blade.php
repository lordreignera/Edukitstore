<header class="sticky top-0 z-30 flex min-h-[72px] items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-7">
    <button type="button" class="grid size-10 shrink-0 place-items-center rounded-md border border-slate-200 text-slate-700 lg:hidden" aria-label="Open navigation" @click="portalMenuOpen = true"><x-ui.icon name="menu" /></button>

    @if ($searchAction)
        <form method="GET" action="{{ $searchAction }}" class="relative hidden w-full max-w-md sm:block">
            <label for="portal-search" class="sr-only">Search</label>
            <x-ui.icon name="search" size="size-[18px]" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input id="portal-search" name="q" value="{{ request('q') }}" placeholder="{{ $searchPlaceholder }}" class="h-10 w-full rounded-md border-slate-200 bg-slate-50 pl-10 pr-4 text-sm focus:border-emerald-600 focus:bg-white focus:ring-emerald-600">
        </form>
    @else
        <p class="hidden text-sm font-bold text-slate-500 sm:block">{{ $portalName }}</p>
    @endif

    <div class="ml-auto flex items-center gap-2">
        <a href="{{ route('profile.show') }}" class="grid size-10 place-items-center rounded-md text-slate-600 hover:bg-slate-100" aria-label="Profile settings"><x-ui.icon name="bell" /></a>
        <div class="relative" x-data="{ userMenuOpen: false }">
            <button type="button" class="flex h-11 items-center gap-2 rounded-md px-2 text-left hover:bg-slate-100" @click="userMenuOpen = !userMenuOpen">
                <span class="grid size-9 place-items-center rounded-full text-xs font-extrabold {{ $palette['soft'] }}">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                <span class="hidden max-w-40 sm:block"><span class="block truncate text-sm font-bold">{{ auth()->user()->name }}</span><span class="block truncate text-xs text-slate-500">{{ $portalName }}</span></span>
                <x-ui.icon name="chevron-down" size="size-4" class="hidden text-slate-400 sm:block" />
            </button>
            <div x-cloak x-show="userMenuOpen" @click.outside="userMenuOpen = false" x-transition class="absolute right-0 mt-2 w-52 rounded-md border border-slate-200 bg-white p-1.5 shadow-xl">
                <a href="{{ route('profile.show') }}" class="flex items-center gap-2 rounded px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><x-ui.icon name="user" size="size-4" />Profile settings</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex w-full items-center gap-2 rounded px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"><x-ui.icon name="logout" size="size-4" />Sign out</button></form>
            </div>
        </div>
    </div>
</header>
