<section class="relative overflow-hidden border-b border-[#dbe8f3] bg-[#eaf7ff]" data-hero-slider>
    <div class="relative min-h-[620px] min-[420px]:min-h-[600px] sm:min-h-[560px] lg:min-h-[590px]">
        @foreach ($heroSlides as $index => $slide)
            <article class="hero-slide {{ $index === 0 ? 'is-active' : '' }} absolute inset-0" data-hero-slide>
                <div class="absolute inset-0 bg-gradient-to-r from-white via-white/95 to-white/45"></div>
                <div class="absolute inset-y-0 right-0 hidden w-[58%] bg-cover bg-center sm:block" style="background-image: url('{{ $slide['image'] }}')">
                    <div class="h-full w-full bg-gradient-to-r from-white/35 via-white/5 to-transparent"></div>
                </div>
                <div class="relative mx-auto grid min-h-[620px] max-w-7xl content-center px-4 py-10 min-[420px]:min-h-[600px] sm:min-h-[560px] sm:px-6 lg:min-h-[590px] lg:px-8">
                    <div class="max-w-[620px]">
                        <p class="inline-flex rounded-md border border-emerald-200 bg-white/90 px-3 py-1.5 text-[10px] font-black uppercase tracking-wide text-emerald-700 shadow-sm sm:text-[11px]">
                            {{ $slide['eyebrow'] }}
                        </p>
                        <h1 class="mt-4 max-w-[640px] text-[30px] font-black leading-[1.08] text-[#07215f] min-[420px]:text-[42px] sm:text-[58px]">
                            {{ \Illuminate\Support\Str::before($slide['title'], $slide['accent']) }}<span class="text-emerald-600">{{ $slide['accent'] }}</span>
                        </h1>
                        <p class="mt-5 max-w-xl text-sm font-semibold leading-7 text-slate-600 sm:text-base">
                            {{ $slide['copy'] }}
                        </p>
                        <div class="mt-7 flex flex-wrap gap-3">
                            <a href="{{ $slide['primary']['route'] }}" class="rounded-md bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-emerald-700">
                                {{ $slide['primary']['label'] }}
                            </a>
                            <a href="{{ $slide['secondary']['route'] }}" class="rounded-md border border-[#d7e4ef] bg-white px-5 py-3 text-sm font-black text-[#07215f] shadow-sm hover:border-emerald-500 hover:text-emerald-700">
                                {{ $slide['secondary']['label'] }}
                            </a>
                        </div>

                        <div class="mt-8 grid max-w-xl grid-cols-2 gap-3 text-[11px] font-extrabold text-[#19366f] sm:grid-cols-4 sm:text-[12px]">
                            <div class="rounded-md bg-white/80 p-3 shadow-sm">Delivery to schools and homes</div>
                            <div class="rounded-md bg-white/80 p-3 shadow-sm">Secure checkout flow</div>
                            <div class="rounded-md bg-white/80 p-3 shadow-sm">Trusted suppliers</div>
                            <div class="rounded-md bg-white/80 p-3 shadow-sm">Supporting learners</div>
                        </div>
                    </div>

                    <div class="mt-7 block h-48 overflow-hidden rounded-md bg-cover bg-center min-[420px]:h-56 sm:hidden" style="background-image: url('{{ $slide['image'] }}')">
                        <span class="sr-only">{{ $slide['eyebrow'] }}</span>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="absolute bottom-5 left-0 right-0">
        <div class="mx-auto flex max-w-7xl items-center gap-2 px-4 sm:px-6 lg:px-8">
            @foreach ($heroSlides as $index => $slide)
                <button type="button" class="hero-dot {{ $index === 0 ? 'is-active' : '' }} h-2.5 w-8 rounded-full bg-[#07215f]/25 transition hover:bg-emerald-500" data-hero-dot="{{ $index }}" aria-label="Show slide {{ $index + 1 }}"></button>
            @endforeach
        </div>
    </div>
</section>
