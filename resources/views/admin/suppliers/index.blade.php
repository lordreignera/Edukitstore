<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Suppliers</h2>
                <p class="mt-1 text-sm text-gray-500">Add supplier rooms/businesses and approve them before fulfilment.</p>
            </div>
            <button type="button" @click="$dispatch('open-admin-modal', 'create-supplier')" class="rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Add supplier</button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-5 border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
            @endif
            <form method="GET" class="mb-5 grid gap-3 border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_180px_160px_auto_auto]">
                <input name="q" value="{{ $search }}" placeholder="Search business, contact or email" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                <select name="status" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">All statuses</option><option value="pending" @selected($status === 'pending')>Pending</option><option value="approved" @selected($status === 'approved')>Approved</option><option value="suspended" @selected($status === 'suspended')>Suspended</option>
                </select>
                <select name="source" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">All sources</option><option value="website" @selected($source === 'website')>Website</option><option value="admin" @selected($source === 'admin')>Admin</option>
                </select>
                <button class="h-10 rounded bg-[#07215f] px-5 text-sm font-bold text-white hover:bg-[#0b2f7c]">Filter</button>
                <a href="{{ route('admin.suppliers.index') }}" class="grid h-10 place-items-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Clear</a>
            </form>
            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    @if (session('status'))
                        <p class="text-sm font-medium text-emerald-700">{{ session('status') }}</p>
                    @else
                        <p class="text-sm text-gray-500">Only approved and active suppliers should be considered for future order sourcing.</p>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Business</th>
                                <th class="px-5 py-3">Contact</th>
                                <th class="px-5 py-3">Supply</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($suppliers as $supplier)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-950">{{ $supplier->business_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $supplier->district ?? 'No district' }}{{ $supplier->address ? ' - '.$supplier->address : '' }}</p>
                                        <p class="mt-1 text-xs font-semibold text-gray-400">{{ ucfirst($supplier->source ?? 'admin') }} submission</p>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        <p>{{ $supplier->contact_person ?? 'No contact person' }}</p>
                                        <p class="text-xs">{{ $supplier->phone ?? $supplier->email ?? 'No phone/email' }}</p>
                                    </td>
                                    <td class="max-w-xs px-5 py-4 text-gray-600">
                                        <p class="line-clamp-2">{{ $supplier->product_categories ?? 'Categories pending' }}</p>
                                        <p class="mt-1 text-xs">{{ $supplier->supply_capacity ?? 'Capacity pending' }}</p>
                                        @if ($supplier->verification_document_path)
                                            <a href="{{ route('admin.suppliers.download', $supplier) }}" class="mt-2 inline-block text-xs font-bold text-emerald-700 hover:text-emerald-800">Download verification</a>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded px-2 py-1 text-xs font-semibold {{ $supplier->is_approved ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $supplier->is_approved ? 'Approved' : 'Pending' }}
                                        </span>
                                        @if ($supplier->is_approved && ! $supplier->is_active)
                                            <span class="ml-1 rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">Suspended</span>
                                        @endif
                                        <p class="mt-2 text-xs {{ $supplier->user?->is_active ? 'text-emerald-700' : 'text-slate-500' }}">{{ $supplier->user ? ($supplier->user->is_active ? 'Account active' : 'Account inactive') : 'No account yet' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="$dispatch('open-admin-modal', 'edit-supplier-{{ $supplier->id }}')" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
                                            @unless ($supplier->is_approved)
                                                <form method="POST" action="{{ route('admin.suppliers.approve', $supplier) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">Approve</button>
                                                </form>
                                            @endunless
                                            @if ($supplier->is_active)
                                                <form method="POST" action="{{ route('admin.suppliers.suspend', $supplier) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Suspend</button>
                                                </form>
                                            @elseif ($supplier->is_approved && $supplier->user_id)
                                                <form method="POST" action="{{ route('admin.suppliers.reinstate', $supplier) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded border border-emerald-300 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Reinstate</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">No suppliers have been added.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $suppliers->links() }}
                </div>
            </section>
        </div>
    </div>
@push('modals')
    <x-admin-modal name="create-supplier" title="Add supplier" description="Create a supplier record for verification and approval.">
        <form method="POST" action="{{ route('admin.suppliers.store') }}">
            @csrf
            <input type="hidden" name="_modal" value="create-supplier">
            @include('admin.suppliers._form', ['supplier' => $supplierForm, 'idPrefix' => 'create-supplier', 'submitLabel' => 'Save supplier'])
        </form>
    </x-admin-modal>

    @foreach ($suppliers as $supplier)
        <x-admin-modal name="edit-supplier-{{ $supplier->id }}" title="Edit supplier" description="Update business and contact information.">
            <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="_modal" value="edit-supplier-{{ $supplier->id }}">
                @include('admin.suppliers._form', ['supplier' => $supplier, 'idPrefix' => 'edit-supplier-'.$supplier->id, 'submitLabel' => 'Save changes'])
            </form>
        </x-admin-modal>
    @endforeach

    @if ($errors->any() && old('_modal'))
        <div x-data x-init="$nextTick(() => $dispatch('open-admin-modal', '{{ old('_modal') }}'))"></div>
    @endif
@endpush
</x-admin-layout>
