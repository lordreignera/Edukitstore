<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name', 'EduKit') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <x-banner />
        <div x-data="{ adminMenuOpen: false }" class="min-h-screen">
            <div x-cloak x-show="adminMenuOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/55 lg:hidden" @click="adminMenuOpen = false"></div>

            @include('admin.partials.sidebar')

            <div class="min-h-screen lg:pl-[248px]">
                @include('admin.partials.topbar')

                @if (isset($header))
                    <div class="border-b border-slate-200 bg-white px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                @endif

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('modals')
        @livewireScripts
    </body>
</html>
