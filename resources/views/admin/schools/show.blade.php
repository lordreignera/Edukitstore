<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold text-emerald-700">Delivery Setup</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">{{ $school->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $school->district?->name ?? 'District pending' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.schools.edit', $school) }}" class="rounded bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">Edit school</a>
                <a href="{{ route('admin.schools.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
            <section class="rounded border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-500">Checkout school</p>
                        <h2 class="mt-1 text-xl font-black text-[#071d4f]">{{ $school->name }}</h2>
                    </div>
                    <span class="rounded px-2 py-1 text-xs font-bold {{ $school->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $school->is_active ? 'Active' : 'Hidden' }}
                    </span>
                </div>

                <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="font-bold text-slate-500">District</dt><dd class="mt-1 text-slate-950">{{ $school->district?->name ?? '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">School code</dt><dd class="mt-1 text-slate-950">{{ $school->school_code ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Distance</dt><dd class="mt-1 text-slate-950">{{ number_format((float) $school->distance_from_warehouse_km, 2) }} km</dd></div>
                    <div><dt class="font-bold text-slate-500">Delivery fee</dt><dd class="mt-1 font-black text-slate-950">UGX {{ number_format($school->delivery_fee) }}</dd></div>
                    <div class="sm:col-span-2"><dt class="font-bold text-slate-500">Location</dt><dd class="mt-1 text-slate-950">{{ $school->location ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Contact person</dt><dd class="mt-1 text-slate-950">{{ $school->contact_person ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Contact phone</dt><dd class="mt-1 text-slate-950">{{ $school->contact_phone ?: '-' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="font-bold text-slate-500">Contact email</dt><dd class="mt-1 text-slate-950">{{ $school->contact_email ?: '-' }}</dd></div>
                </dl>

                @if ($school->notes)
                    <div class="mt-6 border-t border-slate-100 pt-5">
                        <p class="text-sm font-bold text-slate-500">Notes</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $school->notes }}</p>
                    </div>
                @endif
            </section>

            <section class="rounded border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-extrabold text-slate-950">Recent checkout activity</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ number_format($school->shopping_lists_count) }} invoices/orders linked to this school.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Reference</th>
                                <th class="px-5 py-3">Customer</th>
                                <th class="px-5 py-3">Payment</th>
                                <th class="px-5 py-3">Total</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($school->shoppingLists as $invoice)
                                <tr>
                                    <td class="px-5 py-4 font-bold text-slate-950">{{ $invoice->reference }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $invoice->parent_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $invoice->phone }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ \App\Models\ShoppingList::paymentStatuses()[$invoice->payment_status] ?? ucfirst($invoice->payment_status) }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $invoice->estimated_total ? 'UGX '.number_format($invoice->estimated_total) : 'Pending' }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="rounded bg-[#07215f] px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-slate-500">No invoices have used this school yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
