<x-admin-layout>
    <x-slot name="title">Delivery Fee Report</x-slot>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-extrabold text-[#071d4f]">Completed-order fees</h1>
            <p class="mt-1 text-sm text-slate-500">Customer convenience/delivery fees collected on paid orders confirmed as received.</p>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            @include('admin.reports.partials.partner-tabs')
            @include('admin.reports.partials.delivery-fees-content')
        </div>
    </div>
</x-admin-layout>
