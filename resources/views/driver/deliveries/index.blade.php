<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-bold text-emerald-700">Delivery workspace</p>
            <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Assigned deliveries</h1>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-5 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
            @endif

            <section class="border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <p class="text-sm font-semibold text-slate-500">{{ $driver->name }}{{ $driver->phone ? ' | '.$driver->phone : '' }}</p>
                    <p class="mt-1 text-xs text-slate-500">Only paid requests can be confirmed. Once you confirm delivery, the transaction becomes complete.</p>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($deliveries as $delivery)
                        <article class="grid gap-4 px-5 py-5 lg:grid-cols-[1fr_260px]">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-extrabold text-[#071d4f]">{{ $delivery->reference }}</h2>
                                    <span class="rounded bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">{{ \App\Models\ShoppingList::statuses()[$delivery->status] ?? ucfirst($delivery->status) }}</span>
                                    <span class="rounded {{ $delivery->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-2 py-1 text-xs font-bold">{{ ucfirst($delivery->payment_status) }}</span>
                                </div>
                                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                    <div><dt class="font-bold text-slate-500">Customer</dt><dd class="mt-1 text-slate-900">{{ $delivery->parent_name }}</dd></div>
                                    <div><dt class="font-bold text-slate-500">Phone</dt><dd class="mt-1 text-slate-900">{{ $delivery->phone }}</dd></div>
                                    <div><dt class="font-bold text-slate-500">School</dt><dd class="mt-1 text-slate-900">{{ $delivery->school_name ?: '-' }}</dd></div>
                                    <div><dt class="font-bold text-slate-500">Total</dt><dd class="mt-1 text-slate-900">{{ $delivery->estimated_total ? 'UGX '.number_format($delivery->estimated_total) : '-' }}</dd></div>
                                    <div class="sm:col-span-2"><dt class="font-bold text-slate-500">Delivery location</dt><dd class="mt-1 text-slate-900">{{ $delivery->delivery_location ?: '-' }}</dd></div>
                                </dl>
                            </div>

                            <div>
                                @if ($delivery->delivery_confirmed_at)
                                    <div class="rounded border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
                                        Delivered on {{ $delivery->delivery_confirmed_at->format('M d, Y H:i') }}
                                    </div>
                                @elseif ($delivery->payment_status === \App\Models\ShoppingList::PAYMENT_PAID)
                                    <form method="POST" action="{{ route('driver.deliveries.confirm', $delivery) }}" class="space-y-3">
                                        @csrf
                                        @method('PATCH')
                                        <label class="text-sm font-bold text-slate-700" for="delivery_notes_{{ $delivery->id }}">Delivery notes</label>
                                        <textarea id="delivery_notes_{{ $delivery->id }}" name="delivery_notes" rows="3" class="w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Receiver name or handover note"></textarea>
                                        <button class="w-full rounded bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">Confirm delivered</button>
                                    </form>
                                @else
                                    <div class="rounded border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">
                                        Waiting for verified customer payment.
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="px-5 py-10 text-sm text-slate-500">No deliveries assigned yet.</p>
                    @endforelse
                </div>
            </section>

            <div class="mt-6">
                {{ $deliveries->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
