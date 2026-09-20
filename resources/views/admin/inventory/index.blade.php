<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold text-emerald-700">Warehouse Control</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Inventory</h1>
                <p class="mt-1 text-sm text-slate-500">Record stock intake, move stock to the website and track sales profit.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="$dispatch('open-admin-modal', 'import-inventory')" class="inline-flex items-center gap-2 rounded border border-emerald-700 px-4 py-2 text-sm font-bold text-emerald-800 hover:bg-emerald-50">
                    <x-ui.icon name="upload" size="size-4" /> Import CSV
                </button>
                <a href="{{ route('admin.inventory.template') }}" class="inline-flex items-center gap-2 rounded border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    <x-ui.icon name="file" size="size-4" /> Template
                </a>
                <a href="{{ route('admin.inventory.export') }}" class="inline-flex items-center gap-2 rounded border border-blue-700 px-4 py-2 text-sm font-bold text-blue-800 hover:bg-blue-50">
                    <x-ui.icon name="download" size="size-4" /> Export
                </a>
                <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">
                    <x-ui.icon name="plus" size="size-4" /> Add product
                </a>
            </div>
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
            @if (session('inventory_import_errors'))
                <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p class="font-bold">Import notes</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach (session('inventory_import_errors') as $importError)
                            <li>{{ $importError }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('admin.inventory.partials.summary-cards')
            @include('admin.inventory.partials.filters')
            @include('admin.inventory.partials.products-table')
            @include('admin.inventory.partials.recent-movements')
        </div>
    </div>

    @push('modals')
        @include('admin.inventory.partials.import-modal')
        @include('admin.inventory.partials.operation-modals')
        @include('admin.inventory.partials.movement-modals')
        @if (request()->boolean('open_import'))
            <div x-data x-init="$nextTick(() => $dispatch('open-admin-modal', 'import-inventory'))"></div>
        @endif
        @if ($errors->any() && old('_modal'))
            <div x-data x-init="$nextTick(() => $dispatch('open-admin-modal', '{{ old('_modal') }}'))"></div>
        @endif
    @endpush
</x-admin-layout>
