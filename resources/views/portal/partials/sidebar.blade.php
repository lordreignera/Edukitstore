<aside class="fixed inset-y-0 left-0 z-50 flex w-[248px] -translate-x-full flex-col text-white shadow-2xl transition-transform duration-200 lg:translate-x-0 {{ $palette['sidebar'] }}"
    :class="portalMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
    <div class="flex h-[72px] items-center justify-between border-b border-white/10 bg-white px-5">
        <a href="{{ route('dashboard') }}" class="block min-w-0">
            <img src="{{ asset('images/website/edukit-store-logo.png') }}" alt="EduKit Store" class="h-11 w-auto max-w-[176px] object-contain">
        </a>
        <button type="button" class="grid size-10 place-items-center text-[#071d4f] lg:hidden" aria-label="Close navigation" @click="portalMenuOpen = false"><x-ui.icon name="close" /></button>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-6">
        <p class="px-3 text-[11px] font-bold uppercase text-white/60">{{ $portalName }}</p>
        <nav class="mt-3 space-y-1" aria-label="{{ $portalName }} navigation">
            @foreach ($navigation as $item)
                <a href="{{ route($item['route']) }}" class="group flex min-h-11 items-center gap-3 rounded-md px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($item['pattern']) ? 'bg-white shadow-sm '.$palette['active'] : 'text-white/90 hover:bg-white/10 hover:text-white' }}">
                    <x-ui.icon :name="$item['icon']" size="size-[18px]" />
                    <span>{{ $item['label'] }}</span>
                    @if (!empty($item['badge']))<span class="ml-auto rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold">{{ $item['badge'] }}</span>@endif
                </a>
            @endforeach
        </nav>

        <p class="mt-8 px-3 text-[11px] font-bold uppercase text-white/60">EduKit</p>
        <a href="{{ route('website.home') }}" target="_blank" class="mt-3 flex min-h-11 items-center gap-3 rounded-md px-3 py-2.5 text-sm font-semibold text-white/90 transition hover:bg-white/10 hover:text-white">
            <x-ui.icon name="website" size="size-[18px]" /><span>View Store</span><x-ui.icon name="external" size="size-4" class="ml-auto opacity-70" />
        </a>
    </div>

    <div class="border-t border-white/10 p-4 {{ $palette['sidebarFoot'] }}">
        <div class="flex items-center gap-3">
            <div class="grid size-10 shrink-0 place-items-center rounded-full bg-white/15 text-sm font-extrabold">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
            <div class="min-w-0"><p class="truncate text-sm font-bold">{{ auth()->user()->name }}</p><p class="truncate text-xs text-white/65">{{ $portalName }}</p></div>
        </div>
    </div>
</aside>
