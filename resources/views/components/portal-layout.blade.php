@props([
    'title' => null,
    'portalName',
    'navigation' => [],
    'theme' => 'orange',
    'searchAction' => null,
    'searchPlaceholder' => 'Search...',
])

@php
    $palette = [
        'sidebar' => 'bg-[#071d4f] border-r border-orange-500/30',
        'sidebarFoot' => 'bg-[#05163d] border-t border-orange-500/30',
        'soft' => 'bg-orange-100 text-orange-800 ring-1 ring-inset ring-orange-200',
        'active' => 'text-[#071d4f]',
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="session-heartbeat-url" content="{{ route('session.heartbeat', absolute: false) }}">
    <title>{{ $title ? $title.' | ' : '' }}{{ config('app.name', 'EduKit') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/website/EDUKIT_STORE_logo2.png') }}">
    <x-pwa-meta />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @livewireStyles
</head>
<body @if (auth()->check()) data-session-timeout-ms="{{ max(60, (int) config('session.idle_timeout_minutes', 5) * 60 * 1000) }}" data-session-warning-ms="{{ max(0, (int) config('session.idle_warning_seconds', 60) * 1000) }}" data-session-logout-url="{{ route('logout', absolute: false) }}" data-session-login-url="{{ route('login', absolute: false) }}" @endif data-private-page class="bg-slate-50 font-sans text-slate-900 antialiased">
    <x-banner />
    <div x-data="{ portalMenuOpen: false }" class="min-h-screen">
        <div x-cloak x-show="portalMenuOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/55 lg:hidden" @click="portalMenuOpen = false"></div>
        @include('portal.partials.sidebar', ['palette' => $palette])

        <div class="min-h-screen lg:pl-[248px] print:pl-0">
            @include('portal.partials.topbar', ['palette' => $palette])
            @include('components.temporary-password-notice')
            @if (isset($header))
                <div class="border-b border-slate-200 bg-white px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div>
            @endif
            <main>{{ $slot }}</main>
        </div>
    </div>
    @stack('modals')
    @livewireScripts
    <x-session-expiry-notice />
    <x-pwa-install-prompt />
</body>
</html>
