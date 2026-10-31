@props([
    'alt' => 'EduKit Store',
    'class' => 'h-12 w-12 object-contain',
])

<img
    src="{{ asset('images/website/EDUKIT_STORE_logo2.png') }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => $class]) }}
>
