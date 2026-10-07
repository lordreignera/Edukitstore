@php
    $readyCount = $deliveries->getCollection()->filter(fn ($delivery) => $delivery->deliveryStatus() === 'ready_for_delivery')->count();
    $navigation = [
        ['label' => 'Dashboard', 'route' => 'driver.dashboard', 'pattern' => 'driver.dashboard', 'icon' => 'home'],
        ['label' => 'My Deliveries', 'route' => 'driver.deliveries.index', 'pattern' => 'driver.deliveries.*', 'icon' => 'drivers', 'badge' => $readyCount ?: null],
    ];
@endphp
<x-portal-layout title="My Deliveries" portal-name="Delivery Partner" :navigation="$navigation" theme="violet" search-action="{{ route('driver.deliveries.index') }}" search-placeholder="Search reference, customer or phone...">
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-extrabold text-[#071d4f]">My deliveries</h1>
            <p class="mt-1 text-sm text-slate-500">Start each paid journey, mark arrival, then let the customer confirm receipt.</p>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
            @endif

            <form method="GET" class="grid gap-3 rounded-md border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_220px_auto]">
                <input name="q" value="{{ $search }}" placeholder="Reference, customer or phone" class="rounded-md border-slate-300 text-sm">
                <select name="status" class="rounded-md border-slate-300 text-sm">
                    <option value="">All statuses</option>
                    <option value="active" @selected($status === 'active')>All pending trips</option>
                    <option value="ready" @selected($status === 'ready')>Ready to start</option>
                    <option value="in_transit" @selected($status === 'in_transit')>In transit</option>
                    <option value="reached" @selected($status === 'reached')>Reached customer</option>
                    <option value="waiting" @selected($status === 'waiting')>Awaiting payment</option>
                    <option value="completed" @selected($status === 'completed')>Customer confirmed receipt</option>
                </select>
                <button class="rounded-md bg-[#24204f] px-5 py-2.5 text-sm font-bold text-white">Filter</button>
            </form>

            <div class="grid gap-4">
                @forelse ($deliveries as $delivery)
                    @php($journeyStatus = $delivery->deliveryStatus())
                    <article class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="grid gap-5 lg:grid-cols-[1fr_300px]">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-extrabold text-[#071d4f]">{{ $delivery->reference }}</h2>
                                    <span class="rounded bg-violet-50 px-2 py-1 text-xs font-bold text-violet-700">{{ \App\Models\ShoppingList::deliveryStatuses()[$journeyStatus] ?? ucfirst($journeyStatus) }}</span>
                                    <span class="rounded px-2 py-1 text-xs font-bold {{ $delivery->payment_status === \App\Models\ShoppingList::PAYMENT_PAID ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ ucfirst($delivery->payment_status) }}</span>
                                </div>
                                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-3">
                                    <div><dt class="font-bold text-slate-500">Customer</dt><dd>{{ $delivery->parent_name }}</dd></div>
                                    <div><dt class="font-bold text-slate-500">Phone</dt><dd>{{ $delivery->phone }}</dd></div>
                                    <div><dt class="font-bold text-slate-500">School</dt><dd>{{ $delivery->school?->name ?? $delivery->school_name ?? '-' }}</dd></div>
                                    <div><dt class="font-bold text-slate-500">District</dt><dd>{{ $delivery->school?->district?->name ?? '-' }}</dd></div>
                                    <div><dt class="font-bold text-slate-500">Total</dt><dd>UGX {{ number_format($delivery->estimated_total ?? 0) }}</dd></div>
                                    <div><dt class="font-bold text-slate-500">Location</dt><dd>{{ $delivery->delivery_location ?: '-' }}</dd></div>
                                </dl>
                            </div>

                            <div class="space-y-3">
                                @if ($delivery->customer_received_at || $delivery->delivery_confirmed_at)
                                    <div class="rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
                                        Customer confirmed receipt{{ $delivery->customer_received_at ? ' '.$delivery->customer_received_at->format('d M Y, H:i') : '' }}.
                                    </div>
                                @elseif ($journeyStatus === 'ready_for_delivery')
                                    <form method="POST" action="{{ route('driver.deliveries.start', $delivery) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="w-full rounded-md bg-violet-700 px-4 py-3 text-sm font-bold text-white hover:bg-violet-800">Start journey</button>
                                        <p class="mt-2 text-xs leading-5 text-slate-500">This changes the customer status to In transit.</p>
                                    </form>
                                @elseif ($journeyStatus === 'in_transit')
                                    <form method="POST" action="{{ route('driver.deliveries.reached', $delivery) }}" class="space-y-3">
                                        @csrf
                                        @method('PATCH')
                                        <label class="text-sm font-bold" for="delivery_notes_{{ $delivery->id }}">Arrival note <span class="font-normal text-slate-500">(optional)</span></label>
                                        <textarea id="delivery_notes_{{ $delivery->id }}" name="delivery_notes" rows="3" class="w-full rounded-md border-slate-300 text-sm" placeholder="Tell the customer where you are waiting."></textarea>
                                        <button class="w-full rounded-md bg-violet-700 px-4 py-3 text-sm font-bold text-white hover:bg-violet-800">I have reached</button>
                                    </form>
                                @elseif ($journeyStatus === 'awaiting_customer_confirmation')
                                    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">You have marked arrival. Ask the customer to review the items and confirm receipt.</div>
                                @elseif ($delivery->payment_exception)
                                    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">Payment received; order is under stock review.</div>
                                @else
                                    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">Waiting for verified customer payment.</div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-md border border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-500">No deliveries match these filters.</p>
                @endforelse
            </div>
            {{ $deliveries->links() }}
        </div>
    </div>
</x-portal-layout>
