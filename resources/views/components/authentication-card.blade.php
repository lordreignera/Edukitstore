@props([
    'title' => 'Welcome to EduKit',
    'subtitle' => 'Use your account details to continue.',
])

<div class="min-h-screen bg-white lg:grid lg:grid-cols-[minmax(400px,44%)_1fr]">
    @include('auth.partials.brand-panel')

    <div class="relative h-40 overflow-hidden bg-[#071d4f] lg:hidden">
        <img src="{{ asset('images/website/edukit-hero.png') }}" alt="" class="absolute inset-0 h-full w-full object-cover object-center">
        <div class="absolute inset-0 bg-[#071d4f]/55"></div>
        <div class="relative flex h-full items-center px-5">
            <span class="rounded-md bg-white px-3 py-2 shadow-lg">{{ $logo }}</span>
        </div>
    </div>

    <main class="flex min-h-[calc(100vh-10rem)] items-center justify-center bg-slate-50 px-4 py-8 sm:px-8 lg:min-h-screen lg:py-12">
        <div class="w-full max-w-md">
            <div class="mb-10 hidden items-center justify-between gap-4 lg:flex">
                {{ $logo }}
                <a href="{{ route('website.home') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-[#071d4f] hover:text-emerald-700">
                    Back to shop
                    <x-ui.icon name="arrow-right" size="size-4" />
                </a>
            </div>

            <div class="mb-7">
                <p class="text-xs font-extrabold uppercase text-emerald-700">EduKit account</p>
                <h2 class="mt-2 text-3xl font-extrabold text-[#071d4f]">{{ $title }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $subtitle }}</p>
            </div>

            <section class="rounded-md border border-slate-200 border-t-4 border-t-emerald-600 bg-white p-6 shadow-sm sm:p-8">
                {{ $slot }}
            </section>

            <p class="mt-6 text-center text-xs text-slate-500">Secure access to the EduKit school supply platform.</p>
        </div>
    </main>
</div>
