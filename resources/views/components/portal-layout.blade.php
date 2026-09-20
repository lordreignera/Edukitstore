@props([
    'title' => null,
    'portalName',
    'navigation' => [],
    'theme' => 'emerald',
    'searchAction' => null,
    'searchPlaceholder' => 'Search...',
])

@php
    $palette = $theme === 'violet'
        ? ['sidebar' => 'bg-[#24204f]', 'sidebarFoot' => 'bg-[#1b183d]', 'soft' => 'bg-violet-100 text-violet-800', 'active' => 'text-[#24204f]']
        : ['sidebar' => 'bg-[#007a52]', 'sidebarFoot' => 'bg-[#006744]', 'soft' => 'bg-emerald-100 text-emerald-800', 'active' => 'text-[#075d43]'];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | ' : '' }}{{ config('app.name', 'EduKit') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    <x-banner />
    <div x-data="{ portalMenuOpen: false }" class="min-h-screen">
        <div x-cloak x-show="portalMenuOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/55 lg:hidden" @click="portalMenuOpen = false"></div>
        @include('portal.partials.sidebar', ['palette' => $palette])

        <div class="min-h-screen lg:pl-[248px]">
            @include('portal.partials.topbar', ['palette' => $palette])
            @if (isset($header))
                <div class="border-b border-slate-200 bg-white px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div>
            @endif
            <main>{{ $slot }}</main>
        </div>
    </div>
    @stack('modals')
    @livewireScripts
</body>
</html>
