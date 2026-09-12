<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Drivers</h2>
                <p class="mt-1 text-sm text-gray-500">Add delivery people and approve them before assigning delivery jobs.</p>
            </div>
            <button type="button" @click="$dispatch('open-admin-modal', 'create-driver')" class="rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Add delivery partner</button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-5 border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
            @endif
            <form method="GET" class="mb-5 grid gap-3 border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-[minmax(200px,1fr)_150px_160px_150px_auto_auto]">
                <input name="q" value="{{ $search }}" placeholder="Search name, email or vehicle" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                <select name="status" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"><option value="">All statuses</option><option value="pending" @selected($status === 'pending')>Pending</option><option value="approved" @selected($status === 'approved')>Approved</option></select>
                <select name="availability" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"><option value="">Any availability</option><option value="available" @selected($availability === 'available')>Available</option><option value="unavailable" @selected($availability === 'unavailable')>Unavailable</option></select>
                <select name="source" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"><option value="">All sources</option><option value="website" @selected($source === 'website')>Website</option><option value="admin" @selected($source === 'admin')>Admin</option></select>
                <button class="h-10 rounded bg-[#07215f] px-5 text-sm font-bold text-white hover:bg-[#0b2f7c]">Filter</button>
                <a href="{{ route('admin.drivers.index') }}" class="grid h-10 place-items-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Clear</a>
            </form>
            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    @if (session('status'))
                        <p class="text-sm font-medium text-emerald-700">{{ session('status') }}</p>
                    @else
                        <p class="text-sm text-gray-500">For this first version, a delivery can later be completed when the assigned driver confirms delivery.</p>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Driver</th>
                                <th class="px-5 py-3">Vehicle</th>
                                <th class="px-5 py-3">District</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($drivers as $driver)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-950">{{ $driver->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $driver->phone ?? $driver->email ?? 'No phone/email' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        <p>{{ $driver->vehicle_type ?? '-' }}</p>
                                        <p class="text-xs">{{ $driver->vehicle_registration ?? '' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $driver->district ?? '-' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded px-2 py-1 text-xs font-semibold {{ $driver->is_approved ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $driver->is_approved ? 'Approved' : 'Pending' }}
                                        </span>
                                        @unless ($driver->is_available)
                                            <span class="ml-1 rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">Unavailable</span>
                                        @endunless
                                        <p class="mt-2 text-xs {{ $driver->user?->is_active ? 'text-emerald-700' : 'text-slate-500' }}">{{ $driver->user ? ($driver->user->is_active ? 'Account active' : 'Account inactive') : 'No account yet' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="$dispatch('open-admin-modal', 'edit-driver-{{ $driver->id }}')" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
                                            @if ($driver->verification_document_path)
                                                <a href="{{ route('admin.drivers.download', $driver) }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Document</a>
                                            @endif
                                            @unless ($driver->is_approved)
                                                <form method="POST" action="{{ route('admin.drivers.approve', $driver) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">Approve</button>
                                                </form>
                                            @endunless
                                            @if ($driver->is_available)
                                                <form method="POST" action="{{ route('admin.drivers.unavailable', $driver) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Unavailable</button>
                                                </form>
                                            @elseif ($driver->is_approved && $driver->user?->is_active)
                                                <form method="POST" action="{{ route('admin.drivers.available', $driver) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded border border-emerald-300 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Available</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">No drivers have been added.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $drivers->links() }}
                </div>
            </section>
        </div>
    </div>
    @push('modals')
        <x-admin-modal name="create-driver" title="Add delivery partner" description="Create a delivery partner record for verification and approval.">
            <form method="POST" action="{{ route('admin.drivers.store') }}">
                @csrf
                <input type="hidden" name="_modal" value="create-driver">
                @include('admin.drivers._form', ['driver' => $driverForm, 'idPrefix' => 'create-driver', 'submitLabel' => 'Save delivery partner'])
            </form>
        </x-admin-modal>

        @foreach ($drivers as $driver)
            <x-admin-modal name="edit-driver-{{ $driver->id }}" title="Edit delivery partner" description="Update contact, operating area and vehicle information.">
                <form method="POST" action="{{ route('admin.drivers.update', $driver) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_modal" value="edit-driver-{{ $driver->id }}">
                    @include('admin.drivers._form', ['driver' => $driver, 'idPrefix' => 'edit-driver-'.$driver->id, 'submitLabel' => 'Save changes'])
                </form>
            </x-admin-modal>
        @endforeach

        @if ($errors->any() && old('_modal'))
            <div x-data x-init="$nextTick(() => $dispatch('open-admin-modal', '{{ old('_modal') }}'))"></div>
        @endif
    @endpush
</x-admin-layout>
