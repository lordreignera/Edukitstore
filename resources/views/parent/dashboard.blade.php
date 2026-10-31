@php
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'parent.dashboard', 'pattern' => 'parent.dashboard', 'icon' => 'home'],
        ['label' => 'Shop supplies', 'route' => 'website.products.index', 'pattern' => 'website.products.*', 'icon' => 'products'],
        ['label' => 'Upload a list', 'route' => 'website.upload-list', 'pattern' => 'website.upload-list', 'icon' => 'upload'],
        ['label' => 'Track an order', 'route' => 'website.track-order', 'pattern' => 'website.track-order', 'icon' => 'drivers'],
    ];
@endphp

<x-portal-layout title="Parent Dashboard" portal-name="Parent Portal" :navigation="$navigation">
    <x-slot name="header">
        <div><h1 class="text-2xl font-extrabold text-[#071d4f]">Parent Dashboard</h1><p class="mt-1 text-sm text-slate-500">Welcome, {{ auth()->user()->name }}.</p></div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8"><div class="mx-auto max-w-5xl space-y-5">
        <x-dashboard-guide
            eyebrow="Parent workflow"
            title="How to order supplies"
            intro="You can shop from the catalogue, send a complete list for quotation, and follow delivery with the order reference and matching contact details."
            :steps="[
                ['title' => 'Shop supplies', 'description' => 'Browse the catalogue and add the supplies you need to your cart.', 'route' => 'website.products.index', 'action' => 'Browse products'],
                ['title' => 'Send a list for a quote', 'description' => 'Use Upload a list when you already have a list of items and quantities to price.', 'route' => 'website.upload-list', 'action' => 'Upload a list'],
                ['title' => 'Review and pay', 'description' => 'Use the order reference returned by EduKit to review the quote and pay when you are ready.'],
                ['title' => 'Track delivery', 'description' => 'Enter the reference and the same phone number or email used with the order.', 'route' => 'website.track-order', 'action' => 'Track an order'],
                ['title' => 'Confirm receipt', 'description' => 'Inspect the delivery and acknowledge receipt from the order page once everything is received.'],
            ]"
            :notes="[
                'The current parent flow tracks orders by reference and matching contact details; the dashboard does not yet show an account-based order history.',
            ]"
        />
        <section class="rounded-md border border-slate-200 bg-white p-6"><h2 class="text-lg font-bold text-[#071d4f]">School supplies for your learner</h2><p class="mt-2 text-sm leading-6 text-slate-600">Shop the catalogue, send a shopping list for a quote, or track an order using its reference and contact details.</p></section>
        <div class="grid gap-4 md:grid-cols-3">
            <a href="{{ route('website.products.index') }}" class="rounded-md border border-slate-200 bg-white p-5 hover:border-orange-500"><x-ui.icon name="products" size="size-6" class="text-orange-700" /><h2 class="mt-3 font-bold text-[#071d4f]">Shop supplies</h2><p class="mt-1 text-sm text-slate-600">Browse the EduKit catalogue.</p></a>
            <a href="{{ route('website.upload-list') }}" class="rounded-md border border-slate-200 bg-white p-5 hover:border-orange-500"><x-ui.icon name="upload" size="size-6" class="text-orange-700" /><h2 class="mt-3 font-bold text-[#071d4f]">Upload a list</h2><p class="mt-1 text-sm text-slate-600">Send your list for quotation.</p></a>
            <a href="{{ route('website.track-order') }}" class="rounded-md border border-slate-200 bg-white p-5 hover:border-orange-500"><x-ui.icon name="drivers" size="size-6" class="text-orange-700" /><h2 class="mt-3 font-bold text-[#071d4f]">Track an order</h2><p class="mt-1 text-sm text-slate-600">Use your reference and matching phone number or email.</p></a>
        </div>
    </div></div>
</x-portal-layout>
