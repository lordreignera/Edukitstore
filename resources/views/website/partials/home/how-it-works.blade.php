<section id="how-it-works" class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="text-[24px] font-black text-[#07215f] sm:text-[28px]">How EduKit Works</h2>
        <div class="mt-7 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($howItWorks as $item)
                <div class="relative text-center">
                    <span class="mx-auto grid size-11 place-items-center rounded-full bg-emerald-600 text-sm font-black text-white shadow-sm">{{ $item['step'] }}</span>
                    <div class="mx-auto mt-3 grid size-12 place-items-center text-[#07215f]">
                        <svg viewBox="0 0 24 24" class="size-10" fill="none" aria-hidden="true">
                            <path d="{{ $item['icon'] }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <p class="mt-2 font-black text-[#07215f]">{{ $item['title'] }}</p>
                    <p class="mx-auto mt-1 max-w-[220px] text-sm font-semibold leading-6 text-slate-600">{{ $item['copy'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
