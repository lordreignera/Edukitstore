<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="session-heartbeat-url" content="{{ route('session.heartbeat', absolute: false) }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/website/EDUKIT_STORE_logo2.png') }}">
        <x-pwa-meta />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body @if (auth()->check()) data-session-timeout-ms="{{ max(60, (int) config('session.idle_timeout_minutes', 5) * 60 * 1000) }}" data-session-warning-ms="{{ max(0, (int) config('session.idle_warning_seconds', 60) * 1000) }}" data-session-logout-url="{{ route('logout', absolute: false) }}" data-session-login-url="{{ route('login', absolute: false) }}" @endif data-private-page class="bg-slate-50 font-sans text-slate-900 antialiased">
        <x-banner />

        <div class="min-h-screen bg-slate-50">
            @livewire('navigation-menu')
            @include('components.temporary-password-notice')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="border-b border-slate-200 bg-white">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts
        <x-session-expiry-notice />
        <x-pwa-install-prompt />
    </body>
</html>
