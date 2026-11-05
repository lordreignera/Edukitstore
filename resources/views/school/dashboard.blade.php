@php
    $portalName = $isSchoolAdmin ? 'School Administrator' : 'School Receiver';
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'school.dashboard', 'pattern' => 'school.dashboard', 'icon' => 'home'],
        ['label' => 'Order tracking help', 'route' => 'website.track-order', 'pattern' => 'website.track-order', 'icon' => 'drivers'],
    ];
    if ($isSchoolAdmin) {
        $navigation[] = ['label' => 'Browse products', 'route' => 'website.products.index', 'pattern' => 'website.products.*', 'icon' => 'products'];
        $navigation[] = ['label' => 'Upload a list', 'route' => 'website.upload-list', 'pattern' => 'website.upload-list', 'icon' => 'upload'];
    }
@endphp

<x-portal-layout :title="$portalName.' Dashboard'" :portal-name="$portalName" :navigation="$navigation">
    <x-slot name="header">
        <div><h1 class="text-2xl font-extrabold text-[#071d4f]">{{ $portalName }} Dashboard</h1><p class="mt-1 text-sm text-slate-500">Welcome, {{ auth()->user()->name }}.</p></div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8"><div class="mx-auto max-w-5xl space-y-5">
        <section class="rounded-md border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold text-[#071d4f]">{{ $isSchoolAdmin ? 'School supply requests' : 'Receiving deliveries' }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $isSchoolAdmin ? 'Browse supplies, submit a shopping list, or check a submitted order.' : 'Check a delivery using its order reference and the contact details supplied with the order.' }}</p>
        </section>
        <div class="grid gap-4 md:grid-cols-2">
            @if ($isSchoolAdmin)
                <a href="{{ route('website.products.index') }}" class="rounded-md border border-slate-200 bg-white p-5 hover:border-orange-500"><x-ui.icon name="products" size="size-6" class="text-orange-700" /><h2 class="mt-3 font-bold text-[#071d4f]">Browse products</h2><p class="mt-1 text-sm text-slate-600">See the current EduKit catalogue.</p></a>
                <a href="{{ route('website.upload-list') }}" class="rounded-md border border-slate-200 bg-white p-5 hover:border-orange-500"><x-ui.icon name="upload" size="size-6" class="text-orange-700" /><h2 class="mt-3 font-bold text-[#071d4f]">Upload a shopping list</h2><p class="mt-1 text-sm text-slate-600">Send a list for quotation.</p></a>
            @endif
            <a href="{{ route('website.track-order') }}" class="rounded-md border border-slate-200 bg-white p-5 hover:border-orange-500"><x-ui.icon name="drivers" size="size-6" class="text-orange-700" /><h2 class="mt-3 font-bold text-[#071d4f]">Order tracking help</h2><p class="mt-1 text-sm text-slate-600">Customers order as guests and use the order page opened after submission. Phone recovery will be added with OTP.</p></a>
        </div>
    </div></div>
</x-portal-layout>
