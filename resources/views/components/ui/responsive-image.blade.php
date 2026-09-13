@props([
    'src' => null,
    'alt' => '',
    'label' => 'EduKit Supply',
    'imageClass' => 'h-full w-full object-contain p-3',
    'fallbackClass' => 'grid h-full w-full place-items-center px-3 text-center text-xs font-black uppercase tracking-wide text-slate-400',
])

@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $imageClass }}" loading="lazy" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
@endif

<div class="{{ $src ? 'hidden ' : '' }}{{ $fallbackClass }}">
    {{ $label }}
</div>
