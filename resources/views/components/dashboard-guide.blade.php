@props([
    'eyebrow' => 'Getting started',
    'title' => 'Your workspace guide',
    'intro' => null,
    'steps' => [],
    'notes' => [],
])

@php
    $visibleSteps = collect($steps)
        ->filter(fn (array $step): bool => empty($step['permission']) || auth()->user()?->can($step['permission']))
        ->values();
@endphp

<section class="overflow-hidden rounded-md border border-orange-200 bg-white shadow-sm" aria-labelledby="dashboard-guide-title">
    <div class="border-b border-orange-100 bg-orange-50/70 px-5 py-4 sm:px-6">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-md bg-orange-100 text-orange-700">
                <x-ui.icon name="check" size="size-5" stroke-width="2" />
            </span>
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-orange-700">{{ $eyebrow }}</p>
                <h2 id="dashboard-guide-title" class="mt-1 text-lg font-extrabold text-[#071d4f]">{{ $title }}</h2>
                @if ($intro)
                    <p class="mt-1 max-w-4xl text-sm leading-6 text-slate-600">{{ $intro }}</p>
                @endif
            </div>
        </div>
    </div>

    @if ($visibleSteps->isNotEmpty())
        <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-3 sm:p-6">
            @foreach ($visibleSteps as $index => $step)
                <article class="flex gap-3 rounded-md border border-slate-200 bg-white p-4">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-[#071d4f] text-sm font-extrabold text-white">{{ $index + 1 }}</span>
                    <div class="min-w-0">
                        <h3 class="font-extrabold text-[#071d4f]">{{ $step['title'] }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $step['description'] }}</p>
                        @if (!empty($step['route']))
                            <a href="{{ route($step['route']) }}" class="mt-3 inline-flex items-center gap-1 text-xs font-extrabold text-orange-700 hover:text-orange-800">
                                {{ $step['action'] ?? 'Open' }}
                                <x-ui.icon name="arrow-right" size="size-3.5" stroke-width="2" />
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if (count($notes))
        <div class="border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
            <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Important to know</p>
            <ul class="mt-2 grid gap-2 text-sm leading-6 text-slate-600 md:grid-cols-2">
                @foreach ($notes as $note)
                    <li class="flex gap-2"><span class="mt-2 size-1.5 shrink-0 rounded-full bg-orange-500"></span><span>{{ $note }}</span></li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
