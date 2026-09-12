@props([
    'title' => 'Welcome to EduKit',
    'subtitle' => 'Use your account details to continue.',
])

<main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-8 sm:px-6">
    <div class="w-full max-w-lg">
        <div class="mb-6 flex justify-center">
            {{ $logo }}
        </div>

        <div class="text-center">
            <p class="text-xs font-extrabold uppercase text-emerald-700">EduKit account</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#071d4f] sm:text-4xl">{{ $title }}</h1>
            <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">{{ $subtitle }}</p>
        </div>

        <section class="mt-7 rounded-md border border-slate-200 border-t-4 border-t-emerald-600 bg-white p-6 shadow-sm sm:p-8">
            {{ $slot }}
        </section>

        <div class="mt-6 flex flex-col items-center justify-center gap-3 text-center sm:flex-row sm:gap-5">
            <a href="{{ route('website.home') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-[#071d4f] hover:text-emerald-700">
                Back to shop
                <x-ui.icon name="arrow-right" size="size-4" />
            </a>
            <span class="hidden h-4 w-px bg-slate-300 sm:block"></span>
            <p class="text-xs text-slate-500">Secure access to the EduKit platform</p>
        </div>
    </div>
</main>
