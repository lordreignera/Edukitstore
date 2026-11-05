@can('invoices.view')
    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-orders-title">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <div>
                <h2 id="recent-orders-title" class="text-base font-extrabold text-[#071d4f]">Recent orders</h2>
                <p class="mt-0.5 text-xs text-slate-500">Latest customer orders and uploaded list requests.</p>
            </div>
            <a href="{{ route('admin.invoices.index') }}" class="text-sm font-bold text-orange-700 hover:text-orange-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">View all orders <span aria-hidden="true">→</span></a>
        </div>

        <p class="px-4 pt-3 text-xs text-slate-500 md:hidden">Swipe horizontally to see all order details.</p>
        <div class="overflow-x-auto">
            <table class="min-w-[920px] w-full divide-y divide-slate-100 text-left text-sm">
                <caption class="sr-only">Eight most recent orders with customer, item count, payment, delivery and total.</caption>
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3">Order</th>
                        <th scope="col" class="px-4 py-3">Customer / school</th>
                        <th scope="col" class="px-4 py-3">Items</th>
                        <th scope="col" class="px-4 py-3">Payment</th>
                        <th scope="col" class="px-4 py-3">Fulfilment</th>
                        <th scope="col" class="px-4 py-3 text-right">Order total</th>
                        <th scope="col" class="px-4 py-3">Received</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Open order</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($latestOrders as $order)
                        <tr class="hover:bg-orange-50/30">
                            <td class="whitespace-nowrap px-4 py-3">
                                <a href="{{ route('admin.invoices.show', $order) }}" class="font-bold text-[#071d4f] hover:text-orange-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-orange-600">{{ $order->reference }}</a>
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $order->source === \App\Models\ShoppingList::SOURCE_CART ? 'Cart order' : 'Uploaded list' }}</span>
                            </td>
                            <td class="max-w-56 px-4 py-3">
                                <span class="block truncate font-semibold text-slate-800">{{ $order->parent_name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $order->school?->name ?? $order->school_name ?? $order->phone }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                @if ($order->line_items_count > 0)
                                    {{ number_format($order->line_items_count) }}
                                @elseif ($order->source === \App\Models\ShoppingList::SOURCE_CART)
                                    {{ number_format(count($order->cart_items ?? [])) }}
                                @else
                                    List request
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $order->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? 'bg-emerald-50 text-emerald-800' : ($order->payment_status === \App\Models\ShoppingList::PAYMENT_FAILED ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-800') }}">{{ \App\Models\ShoppingList::paymentStatuses()[$order->payment_status] ?? ucfirst($order->payment_status) }}</span>
                                @if ($order->payment_provider === 'demo')<span class="mt-1 block text-[10px] font-bold uppercase text-amber-700">Demo</span>@endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $order->deliveryStatus() === 'delivered' ? 'bg-emerald-50 text-emerald-800' : ($order->deliveryStatus() === 'under_review' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-700') }}">{{ \App\Models\ShoppingList::deliveryStatuses()[$order->deliveryStatus()] ?? ucfirst($order->deliveryStatus()) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-bold tabular-nums text-slate-800">{{ $order->estimated_total !== null ? 'UGX '.number_format($order->estimated_total) : 'Not quoted' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ $order->created_at?->format('d M Y, H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right"><a href="{{ route('admin.invoices.show', $order) }}" class="rounded px-2 py-1 text-xs font-bold text-orange-700 hover:bg-orange-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-orange-600">Open<span class="sr-only"> {{ $order->reference }}</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center"><p class="text-sm font-semibold text-slate-700">No orders yet</p><p class="mt-1 text-xs text-slate-500">New customer orders and uploaded school lists will appear here.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endcan
