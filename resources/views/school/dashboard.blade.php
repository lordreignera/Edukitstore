@php
    $portalName = $isSchoolAdmin ? 'School Administrator' : 'School Receiver';
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'school.dashboard', 'pattern' => 'school.dashboard', 'icon' => 'home'],
        ['label' => 'Track an order', 'route' => 'website.track-order', 'pattern' => 'website.track-order', 'icon' => 'drivers'],
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
        <x-dashboard-guide
            eyebrow="School workflow"
            :title="$isSchoolAdmin ? 'How to request school supplies' : 'How to receive a school delivery'"
            :intro="$isSchoolAdmin ? 'Browse the catalogue or send a shopping list for quotation, then use the order reference and matching contact details to follow delivery.' : 'Use the order reference and the matching contact details supplied with the order to check progress and complete receipt.'"
            :steps="$isSchoolAdmin ? [
                ['title' => 'Browse the catalogue', 'description' => 'Review the current EduKit products before deciding whether to shop directly or send a full list.', 'route' => 'website.products.index', 'action' => 'Browse products'],
                ['title' => 'Send a shopping list', 'description' => 'Upload the required items and quantities so EduKit can prepare a quotation.', 'route' => 'website.upload-list', 'action' => 'Upload a list'],
                ['title' => 'Review the quote', 'description' => 'Use the reference returned after submission to open the quote, review the items and complete payment when ready.'],
                ['title' => 'Track the order', 'description' => 'Enter the order reference and the same phone number or email used with the request.', 'route' => 'website.track-order', 'action' => 'Track an order'],
                ['title' => 'Confirm receipt', 'description' => 'After delivery, inspect the items and acknowledge receipt from the order page.'],
            ] : [
                ['title' => 'Get the order details', 'description' => 'Ask the school office or order contact for the reference and the phone number or email used with the order.'],
                ['title' => 'Track the delivery', 'description' => 'Use those matching details to view the current delivery status.', 'route' => 'website.track-order', 'action' => 'Track an order'],
                ['title' => 'Check the delivered items', 'description' => 'When the driver arrives, compare the delivery with the order before accepting it.'],
                ['title' => 'Confirm receipt', 'description' => 'Acknowledge receipt from the order page after the delivery has been checked.'],
            ]"
            :notes="[
                'The current tracking flow uses an order reference plus matching phone number or email; it does not yet display an account-based order history.',
            ]"
        />
        <section class="rounded-md border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold text-[#071d4f]">{{ $isSchoolAdmin ? 'School supply requests' : 'Receiving deliveries' }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $isSchoolAdmin ? 'Browse supplies, submit a shopping list, or check a submitted order.' : 'Check a delivery using its order reference and the contact details supplied with the order.' }}</p>
        </section>
        <div class="grid gap-4 md:grid-cols-2">
            @if ($isSchoolAdmin)
                <a href="{{ route('website.products.index') }}" class="rounded-md border border-slate-200 bg-white p-5 hover:border-orange-500"><x-ui.icon name="products" size="size-6" class="text-orange-700" /><h2 class="mt-3 font-bold text-[#071d4f]">Browse products</h2><p class="mt-1 text-sm text-slate-600">See the current EduKit catalogue.</p></a>
                <a href="{{ route('website.upload-list') }}" class="rounded-md border border-slate-200 bg-white p-5 hover:border-orange-500"><x-ui.icon name="upload" size="size-6" class="text-orange-700" /><h2 class="mt-3 font-bold text-[#071d4f]">Upload a shopping list</h2><p class="mt-1 text-sm text-slate-600">Send a list for quotation.</p></a>
            @endif
            <a href="{{ route('website.track-order') }}" class="rounded-md border border-slate-200 bg-white p-5 hover:border-orange-500"><x-ui.icon name="drivers" size="size-6" class="text-orange-700" /><h2 class="mt-3 font-bold text-[#071d4f]">Track an order</h2><p class="mt-1 text-sm text-slate-600">Use the reference and matching phone number or email to view delivery status.</p></a>
        </div>
    </div></div>
</x-portal-layout>
