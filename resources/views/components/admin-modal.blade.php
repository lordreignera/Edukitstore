@props([
    'name',
    'title',
    'description' => null,
    'maxWidth' => 'max-w-3xl',
])

<div
    x-data="{ open: false }"
    x-on:open-admin-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-admin-modal.window="if (!$event.detail || $event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
    x-cloak
    x-show="open"
    class="fixed inset-0 z-[70] overflow-y-auto p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $name }}-title"
>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-950/60" @click="open = false"></div>

    <div class="relative mx-auto flex min-h-full items-start justify-center py-4 sm:items-center">
        <div x-show="open" x-transition class="w-full {{ $maxWidth }} overflow-hidden rounded-lg bg-white shadow-2xl">
            <header class="flex items-start justify-between gap-5 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div>
                    <h2 id="{{ $name }}-title" class="text-lg font-extrabold text-[#071d4f]">{{ $title }}</h2>
                    @if ($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif
                </div>
                <button type="button" class="grid size-9 shrink-0 place-items-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900" @click="open = false" aria-label="Close">
                    <x-ui.icon name="close" size="size-5" />
                </button>
            </header>
            <div class="max-h-[calc(100vh-10rem)] overflow-y-auto p-5 sm:p-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
