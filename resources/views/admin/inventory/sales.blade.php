<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-orange-700">Inventory / Stock sold</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Sales History</h1>
                <p class="mt-1 text-sm text-slate-500">Verified paid EduKit stock sales. Demo/test payments are excluded from totals by default.</p>
            </div>
            <a href="{{ route('admin.inventory.index') }}" class="inline-flex min-h-10 items-center gap-2 self-start rounded-md border border-orange-300 px-4 py-2 text-sm font-bold text-orange-800 hover:bg-orange-50">Stock overview</a>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            <form method="GET" action="{{ route('admin.inventory.sales') }}" class="filter-bar">
                <input name="q" value="{{ $search }}" maxlength="100" aria-label="Search paid stock sales" placeholder="Search product, SKU or order reference" class="h-9 min-w-0 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600 sm:col-span-2 xl:col-span-1">
                <input type="date" name="from" value="{{ $from }}" aria-label="Sales from date" class="h-9 min-w-0 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                <input type="date" name="to" value="{{ $to }}" aria-label="Sales to date" class="h-9 min-w-0 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                <label class="flex min-h-9 items-center gap-2 whitespace-nowrap text-xs font-semibold text-slate-700 sm:col-span-2 xl:col-span-1"><input type="checkbox" name="include_demo" value="1" @checked($includeDemo) class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">Include demo payments</label>
                <button class="h-9 rounded bg-[#07215f] px-4 text-sm font-bold text-white hover:bg-orange-700">Filter</button>
                <a href="{{ route('admin.inventory.sales') }}" class="inline-flex h-9 items-center justify-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Clear</a>
            </form>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Paid sales summary">
                <article class="rounded-md border border-orange-200 bg-white p-4"><p class="text-xs font-bold uppercase text-orange-800">Units sold</p><p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['units']) }}</p><p class="mt-1 text-xs text-slate-500">{{ $includeDemo ? 'Includes demo/test payments' : 'Demo/test payments excluded' }}</p></article>
                <article class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase text-slate-500">Orders with stock sales</p><p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($stats['orders']) }}</p><p class="mt-1 text-xs text-slate-500">Orders that have inventory sale records</p></article>
                <article class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase text-slate-500">Item sales revenue</p><p class="mt-2 text-2xl font-extrabold text-slate-950">UGX {{ number_format($stats['revenue']) }}</p><p class="mt-1 text-xs text-slate-500">Excludes delivery and convenience fees</p></article>
                <article class="rounded-md border border-red-200 bg-red-50 p-4"><p class="text-xs font-bold uppercase text-red-800">Unavailable stock write-off</p><p class="mt-2 text-2xl font-extrabold text-red-950">UGX {{ number_format($stats['writeoff_cost']) }}</p><p class="mt-1 text-xs text-red-800">Cost of paid units not delivered</p></article>
                <article class="rounded-md border {{ $stats['profit'] < 0 ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50' }} p-4">
                    <p class="text-xs font-bold uppercase {{ $stats['profit'] < 0 ? 'text-red-800' : 'text-emerald-800' }}">Gross profit after write-offs</p>
                    <p class="mt-2 text-2xl font-extrabold {{ $stats['profit'] < 0 ? 'text-red-950' : 'text-emerald-950' }}">UGX {{ number_format($stats['profit']) }}</p>
                    <p class="mt-1 text-xs {{ $stats['profit'] < 0 ? 'text-red-800' : 'text-emerald-800' }}">Sale margin less unavailable stock cost</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-extrabold text-slate-950">Paid stock sales</h2><p class="mt-1 text-xs text-slate-500">Units show quantities packed for delivery; unavailable units are listed separately in Inventory Movement History. Pending orders remain in Orders &amp; Fulfilment. {{ $includeDemo ? 'Demo/test orders are included.' : 'Demo/test orders are excluded unless selected above.' }}</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase text-slate-500"><tr><th class="px-5 py-3">Sold</th><th class="px-5 py-3">Order</th><th class="px-5 py-3">Product</th><th class="px-5 py-3">Units</th><th class="px-5 py-3">Revenue</th><th class="px-5 py-3">Cost</th><th class="px-5 py-3">Profit</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($movements as $movement)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs font-semibold text-slate-600">{{ $movement->occurred_at?->format('d M Y, H:i') }}</td>
                                    <td class="px-5 py-4 font-semibold">
                                        @if ($movement->shoppingList && auth()->user()->can('invoices.view'))
                                            <a href="{{ route('admin.invoices.show', $movement->shoppingList) }}" class="text-orange-700 hover:underline">{{ $movement->shoppingList->reference }}</a>
                                        @else
                                            {{ $movement->shoppingList?->reference ?? $movement->reference ?? '—' }}
                                        @endif
                                        @if ($movement->shoppingList?->payment_provider === 'demo')<span class="mt-1 block text-[10px] font-extrabold uppercase text-amber-700">Test payment</span>@endif
                                    </td>
                                    <td class="px-5 py-4"><p class="font-bold text-slate-950">{{ $movement->product_name ?? $movement->product?->name ?? 'Deleted product' }}</p><p class="mt-1 text-xs text-slate-500">{{ $movement->sku ?? 'No SKU' }}</p></td>
                                    <td class="px-5 py-4 font-extrabold text-emerald-700">{{ number_format($movement->quantity) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">UGX {{ number_format($movement->total_revenue) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">UGX {{ number_format($movement->total_cost) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 font-extrabold text-emerald-700">UGX {{ number_format($movement->profit) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <button type="button" @click="$dispatch('open-admin-modal', 'sale-details-{{ $movement->id }}')" class="rounded border border-orange-200 px-3 py-1.5 text-xs font-bold text-orange-800 hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-1">View details</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-5 py-12 text-center"><p class="font-semibold text-slate-700">No paid stock sales match these filters.</p><p class="mt-1 text-xs text-slate-500">Sales are recorded here after payment is verified.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $movements->links() }}</div>
            </section>
        </div>
    </div>

    @push('modals')
        @foreach ($movements as $movement)
            @include('admin.inventory.partials.sale-details-modal', ['movement' => $movement])
        @endforeach
    @endpush
</x-admin-layout>
