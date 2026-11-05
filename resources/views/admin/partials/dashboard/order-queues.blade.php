@canany(['invoices.view', 'shopping-lists.view'])
    <section class="overflow-hidden rounded-md border border-orange-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-orange-100 bg-orange-50/70 px-5 py-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-wide text-orange-800">Order operations</p>
                <h2 class="mt-1 text-lg font-extrabold text-[#071d4f]">Orders &amp; fulfilment</h2>
                <p class="mt-1 text-sm text-slate-600">Follow each order from request and payment through driver assignment and confirmed delivery.</p>
            </div>
            @can('invoices.view')
                <a href="{{ route('admin.invoices.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-md bg-[#07215f] px-4 py-2 text-sm font-bold text-white hover:bg-orange-700">View all orders</a>
            @endcan
        </div>

        <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($orderQueues as $queue)
                @can($queue['permission'])
                    <a href="{{ $queue['route'] }}" class="group flex min-h-28 items-start justify-between gap-3 rounded-md border border-slate-200 border-l-4 border-l-orange-500 bg-white p-4 transition hover:border-orange-300 hover:bg-orange-50/50">
                        <span class="min-w-0">
                            <span class="block text-sm font-extrabold text-[#071d4f] group-hover:text-orange-800">{{ $queue['label'] }}</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $queue['description'] }}</span>
                        </span>
                        <span class="grid size-10 shrink-0 place-items-center rounded-md bg-orange-50 text-lg font-black text-orange-800">{{ number_format($queue['count']) }}</span>
                    </a>
                @endcan
            @endforeach
        </div>
    </section>
@endcanany
