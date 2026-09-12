@php
    $queue = [
        ['label' => 'Shopping lists to review', 'value' => $stats['pending_shopping_lists'], 'route' => 'admin.shopping-lists.index', 'icon' => 'list', 'tone' => 'bg-violet-50 text-violet-700'],
        ['label' => 'Supplier applications', 'value' => $stats['pending_suppliers'], 'route' => 'admin.suppliers.index', 'icon' => 'suppliers', 'tone' => 'bg-emerald-50 text-emerald-700'],
        ['label' => 'Driver approvals', 'value' => $stats['pending_drivers'], 'route' => 'admin.drivers.index', 'icon' => 'drivers', 'tone' => 'bg-amber-50 text-amber-700'],
    ];
@endphp

<section class="rounded-md border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h3 class="text-sm font-extrabold text-slate-950">Action Centre</h3>
        <p class="mt-0.5 text-xs text-slate-500">Items waiting for your attention</p>
    </div>
    <div class="space-y-2 p-3">
        @foreach ($queue as $item)
            <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-md p-3 transition hover:bg-slate-50">
                <span class="grid size-10 shrink-0 place-items-center rounded-md {{ $item['tone'] }}"><x-ui.icon :name="$item['icon']" size="size-5" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold text-slate-800">{{ $item['label'] }}</span>
                    <span class="block text-xs text-slate-500">Open management page</span>
                </span>
                <span class="grid size-8 place-items-center rounded-full bg-slate-100 text-sm font-extrabold text-slate-900">{{ $item['value'] }}</span>
            </a>
        @endforeach
    </div>
    <div class="border-t border-slate-100 p-4">
        <a href="{{ route('admin.products.create') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-[#071d4f] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
            <x-ui.icon name="plus" size="size-4" /> Add a product
        </a>
    </div>
</section>
