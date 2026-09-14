<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold text-emerald-700">Warehouse Control</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Inventory</h1>
                <p class="mt-1 text-sm text-slate-500">Record stock intake, move stock to the website and track sales profit.</p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">
                <x-ui.icon name="plus" size="size-4" /> Add product
            </a>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
            @endif

            @include('admin.inventory.partials.summary-cards')
            @include('admin.inventory.partials.filters')
            @include('admin.inventory.partials.products-table')
            @include('admin.inventory.partials.recent-movements')
        </div>
    </div>

    @push('modals')
        @include('admin.inventory.partials.operation-modals')
    @endpush
</x-admin-layout>
