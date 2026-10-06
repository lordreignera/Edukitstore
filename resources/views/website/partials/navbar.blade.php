@php
    $cartCount = array_sum(session('cart', []));
    $navLinks = [
        ['label' => 'Home', 'route' => route('website.home'), 'active' => request()->routeIs('website.home')],
        ['label' => 'Shop', 'route' => route('website.products.index'), 'active' => request()->routeIs('website.products.*')],
        ['label' => 'Upload List', 'route' => route('website.upload-list'), 'active' => request()->routeIs('website.upload-list*')],
        ['label' => 'Schools', 'route' => route('website.schools'), 'active' => request()->routeIs('website.schools')],
        ['label' => 'How It Works', 'route' => route('website.home').'#how-it-works', 'active' => false],
        ['label' => 'For Suppliers', 'route' => route('website.suppliers'), 'active' => request()->routeIs('website.suppliers*')],
        ['label' => 'For Drivers', 'route' => route('website.drivers'), 'active' => request()->routeIs('website.drivers*')],
        ['label' => 'Track Order', 'route' => route('website.track-order'), 'active' => request()->routeIs('website.track-order')],
        ['label' => 'Help', 'route' => route('website.help'), 'active' => request()->routeIs('website.help')],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-[#dce8f2] bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid min-h-[70px] grid-cols-[1fr_auto_auto] items-center gap-2 py-2 xl:grid-cols-[230px_minmax(420px,1fr)_auto] xl:gap-5">
            <a href="{{ route('website.home') }}" class="flex min-w-0 items-center">
                <img src="/images/website/edukit-store-logo.png" alt="EduKit Store" class="h-11 w-auto max-w-[172px] object-contain sm:h-12 sm:max-w-[210px] lg:h-14 lg:max-w-none">
            </a>

            <form id="store-search-form" method="GET" action="{{ route('website.products.index') }}" class="relative order-4 col-span-3 flex h-12 rounded-md border-2 border-[#07215f] bg-white shadow-sm xl:order-none xl:col-span-1">
                <label for="store-search" class="sr-only">Search EduKit products</label>
                <input id="store-search" name="search" value="{{ request('search') }}" maxlength="100" autocomplete="off" aria-controls="store-search-suggestions" aria-expanded="false" placeholder="Search books, uniforms, bags, toiletries..." class="min-w-0 flex-1 border-0 px-3 text-[13px] font-semibold text-slate-700 placeholder:text-slate-400 focus:ring-0 sm:px-4">
                <button class="grid w-14 shrink-0 place-items-center bg-[#07215f] text-white hover:bg-emerald-700" aria-label="Search">
                    <x-ui.icon name="search" size="size-5" />
                </button>
                <div id="store-search-suggestions" role="status" aria-live="polite" class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-md border border-[#dbe8f3] bg-white shadow-xl"></div>
            </form>

            <div class="flex items-center justify-end gap-1.5 text-sm font-extrabold text-[#07215f] sm:gap-2">
                <a href="{{ route('website.track-order') }}" class="hidden min-h-10 items-center rounded-md px-3 text-xs hover:bg-slate-50 hover:text-emerald-700 xl:inline-flex">Track order</a>
                <a href="{{ route('website.cart.index') }}" class="relative grid size-10 place-items-center rounded-md hover:bg-slate-50 hover:text-emerald-700" aria-label="Cart">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 4h2l2.3 11.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.5L21 8H6.2M10 21h.01M18 21h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    @if ($cartCount > 0)
                        <span class="absolute -right-1 -top-1 grid size-5 place-items-center rounded-full bg-emerald-600 text-[10px] font-black text-white">{{ $cartCount }}</span>
                    @endif
                </a>

                <a href="{{ route('login') }}" class="hidden min-h-10 items-center gap-1 rounded-md px-2.5 hover:bg-slate-50 hover:text-emerald-700 sm:inline-flex">
                    <x-ui.icon name="user" size="size-4" />
                    Sign In
                </a>

                <a href="{{ route('website.join') }}" class="hidden rounded-md bg-emerald-600 px-4 py-2.5 text-white shadow-sm hover:bg-emerald-700 min-[460px]:inline-flex">Join EduKit</a>

                <details class="group relative xl:hidden">
                    <summary class="grid size-10 cursor-pointer list-none place-items-center rounded-md border border-[#d8e5f0] bg-white text-[#07215f] hover:border-emerald-500 [&::-webkit-details-marker]:hidden" aria-label="Open menu">
                        <span class="group-open:hidden"><x-ui.icon name="menu" size="size-5" /></span>
                        <span class="hidden group-open:block"><x-ui.icon name="close" size="size-5" /></span>
                    </summary>

                    <div class="absolute right-0 top-12 w-[min(88vw,360px)] overflow-hidden rounded-md border border-[#d8e5f0] bg-white shadow-xl">
                        <nav class="grid p-2 text-sm font-extrabold text-[#19366f]" aria-label="Mobile navigation">
                            @foreach ($navLinks as $link)
                                <a href="{{ $link['route'] }}" class="rounded-md px-4 py-3 {{ $link['active'] ? 'bg-emerald-50 text-emerald-700' : 'hover:bg-slate-50 hover:text-emerald-700' }}">{{ $link['label'] }}</a>
                            @endforeach
                        </nav>
                        <div class="grid gap-2 border-t border-slate-100 p-3 min-[460px]:hidden">
                            <a href="{{ route('login') }}" class="rounded-md border border-[#d8e5f0] px-4 py-3 text-center text-sm font-black text-[#07215f]">Sign In</a>
                            <a href="{{ route('website.join') }}" class="rounded-md bg-emerald-600 px-4 py-3 text-center text-sm font-black text-white">Join EduKit</a>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>

    <div class="hidden border-t border-[#eef4f8] bg-[#07215f] xl:block">
        <nav class="mx-auto flex max-w-7xl items-center gap-5 px-8 py-2.5 text-[13px] font-extrabold text-white" aria-label="Primary navigation">
            <a href="{{ route('website.products.index') }}" class="inline-flex items-center gap-2 rounded bg-white/10 px-3 py-2 hover:bg-white/20">
                <x-ui.icon name="menu" size="size-4" />
                All categories
            </a>
            @foreach ($navLinks as $link)
                <a href="{{ $link['route'] }}" class="{{ $link['active'] ? 'text-emerald-300' : 'text-white/90 hover:text-emerald-200' }} whitespace-nowrap py-2">{{ $link['label'] }}</a>
            @endforeach
        </nav>
    </div>
</header>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('store-search-form');
    const input = document.getElementById('store-search');
    const panel = document.getElementById('store-search-suggestions');
    if (!form || !input || !panel || !window.fetch) return;
    let timer;
    let controller;
    const close = () => { panel.classList.add('hidden'); input.setAttribute('aria-expanded', 'false'); };
    input.addEventListener('input', () => {
        clearTimeout(timer);
        controller?.abort();
        const search = input.value.trim();
        if (!search) { close(); return; }
        timer = setTimeout(() => {
            controller = new AbortController();
            const url = new URL(@json(route('website.products.suggest')));
            url.searchParams.set('search', search);
            fetch(url, { signal: controller.signal })
                .then(response => { if (!response.ok) throw new Error('Suggestions failed'); return response.json(); })
                .then(data => {
                    if (input.value.trim() !== search) return;
                    panel.replaceChildren();
                    if (!data.products.length) {
                        const empty = document.createElement('p');
                        empty.className = 'p-3 text-sm text-slate-500';
                        empty.textContent = 'No matching products';
                        panel.append(empty);
                    }
                    data.products.forEach(product => {
                        const link = document.createElement('a');
                        link.href = product.url;
                        link.className = 'flex justify-between gap-3 border-b border-slate-100 px-3 py-2.5 text-sm hover:bg-emerald-50';
                        const name = document.createElement('span');
                        name.className = 'font-bold text-[#07215f]';
                        name.textContent = product.name;
                        const price = document.createElement('span');
                        price.className = 'shrink-0 text-slate-600';
                        price.textContent = product.price;
                        link.append(name, price);
                        panel.append(link);
                    });
                    const all = document.createElement('a');
                    all.href = new URL(form.action + '?search=' + encodeURIComponent(search));
                    all.className = 'block bg-slate-50 px-3 py-2.5 text-sm font-bold text-emerald-700';
                    all.textContent = 'View all results';
                    panel.append(all);
                    panel.classList.remove('hidden');
                    input.setAttribute('aria-expanded', 'true');
                })
                .catch(() => close());
        }, 200);
    });
    input.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
    document.addEventListener('click', event => { if (!form.contains(event.target)) close(); });
})();
</script>
@endpush
