@props([
    'name',
    'size' => 'size-5',
    'strokeWidth' => 1.8,
])

<svg {{ $attributes->merge(['class' => $size]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('home')
            <path d="m3 10 9-7 9 7" />
            <path d="M5 9v11h14V9" />
            <path d="M9 20v-6h6v6" />
            @break
        @case('products')
            <path d="m7.5 4.3 9 5.2" />
            <path d="M3 7.5 12 13l9-5.5" />
            <path d="M12 22V13" />
            <path d="m21 7.5-9-5-9 5v9l9 5.5 9-5.5Z" />
            @break
        @case('warehouse')
            <path d="M3 21V9l9-5 9 5v12" />
            <path d="M7 21v-8h10v8" />
            <path d="M9 17h6" />
            <path d="M9 13h6" />
            <path d="M3 9h18" />
            @break
        @case('list')
            <path d="M8 6h13" />
            <path d="M8 12h13" />
            <path d="M8 18h13" />
            <path d="M3 6h.01" />
            <path d="M3 12h.01" />
            <path d="M3 18h.01" />
            @break
        @case('suppliers')
            <path d="M3 10h18" />
            <path d="m5 6 2-3h10l2 3" />
            <path d="M5 10v10h14V10" />
            <path d="M9 20v-6h6v6" />
            @break
        @case('drivers')
            <path d="M10 17h4V5H2v12h3" />
            <path d="M14 9h4l4 4v4h-3" />
            <circle cx="7.5" cy="17.5" r="2.5" />
            <circle cx="16.5" cy="17.5" r="2.5" />
            @break
        @case('website')
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18" />
            <path d="M12 3a15 15 0 0 1 0 18" />
            <path d="M12 3a15 15 0 0 0 0 18" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-4-4" />
            @break
        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
            <path d="M10 21h4" />
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('logout')
            <path d="M10 17l5-5-5-5" />
            <path d="M15 12H3" />
            <path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('tag')
            <path d="M20 13 13 20a2 2 0 0 1-2.8 0L4 13.8V4h9.8L20 10.2a2 2 0 0 1 0 2.8Z" />
            <path d="M8 8h.01" />
            @break
        @case('school')
            <path d="M4 21V8l8-5 8 5v13" />
            <path d="M2 21h20" />
            <path d="M9 21v-6h6v6" />
            <path d="M8 10h.01M12 10h.01M16 10h.01" />
            @break
        @case('invoice')
            <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z" />
            <path d="M9 8h6M9 12h6M9 16h4" />
            @break
        @case('edit')
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z" />
            @break
        @case('trash')
            <path d="M3 6h18" />
            <path d="M8 6V4h8v2" />
            <path d="M19 6l-1 14H6L5 6" />
            <path d="M10 11v5M14 11v5" />
            @break
        @case('arrow-right')
            <path d="M5 12h14M13 6l6 6-6 6" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
            @break
        @case('check')
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.5 2.5L16 9" />
            @break
        @case('alert')
            <path d="M10.3 3.7 2.4 18a2 2 0 0 0 1.8 3h15.6a2 2 0 0 0 1.8-3L13.7 3.7a2 2 0 0 0-3.4 0Z" />
            <path d="M12 9v4M12 17h.01" />
            @break
        @case('external')
            <path d="M15 3h6v6" />
            <path d="m10 14 11-11" />
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
            @break
        @case('lock')
            <rect x="4" y="10" width="16" height="11" rx="2" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="m3 7 9 6 9-6" />
            @break
        @case('phone')
            <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.7.6 2.5a2 2 0 0 1-.5 2.1L8 9.5a16 16 0 0 0 6.5 6.5l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.6.5 2.5.6a2 2 0 0 1 1.7 2Z" />
            @break
        @case('map-pin')
            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z" />
            <circle cx="12" cy="10" r="3" />
            @break
        @case('facebook')
            <path d="M14 8h2V4h-2c-3 0-5 2-5 5v2H7v4h2v7h4v-7h3l1-4h-4V9c0-.6.4-1 1-1Z" />
            @break
        @case('x-social')
            <path d="m4 4 16 16" />
            <path d="M20 4 4 20" />
            @break
        @case('instagram')
            <rect x="3" y="3" width="18" height="18" rx="5" />
            <circle cx="12" cy="12" r="4" />
            <path d="M17.5 6.5h.01" />
            @break
        @case('youtube')
            <path d="M22 12s0-3.4-.4-5a2.8 2.8 0 0 0-2-2C17.8 4.5 12 4.5 12 4.5s-5.8 0-7.6.5a2.8 2.8 0 0 0-2 2C2 8.6 2 12 2 12s0 3.4.4 5a2.8 2.8 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.8 2.8 0 0 0 2-2c.4-1.6.4-5 .4-5Z" />
            <path d="m10 9 5 3-5 3V9Z" />
            @break
        @case('linkedin')
            <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4V9h4v2" />
            <path d="M2 9h4v12H2z" />
            <circle cx="4" cy="4" r="2" />
            @break
        @case('play-store')
            <path d="M5 3v18l14-9L5 3Z" />
            <path d="m5 3 10 9L5 21" />
            @break
        @case('app-store')
            <path d="M8 16h8" />
            <path d="m9 13 3-6 3 6" />
            <path d="M6 21h12a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3Z" />
            @break
        @case('eye')
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
            <circle cx="12" cy="12" r="2.5" />
            @break
        @case('eye-off')
            <path d="m3 3 18 18" />
            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
            <path d="M9.8 5.2A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-2 2.8" />
            <path d="M6.6 6.6C3.6 8.5 2 12 2 12s3.5 7 10 7a9.8 9.8 0 0 0 4.2-.9" />
            @break
    @endswitch
</svg>
