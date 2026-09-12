<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <h2 class="text-[24px] font-black text-[#07215f] sm:text-[28px]">Why Choose EduKit?</h2>
    <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-6">
        @foreach ($whyChoose as $item)
            <div class="text-center">
                <div class="mx-auto grid size-12 place-items-center text-emerald-600">
                    <svg viewBox="0 0 24 24" class="size-9" fill="none" aria-hidden="true">
                        <path d="{{ $item['icon'] }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <p class="mx-auto mt-3 max-w-[150px] text-[13px] font-black leading-5 text-[#07215f]">{{ $item['reason'] }}</p>
            </div>
        @endforeach
    </div>
</section>
