<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Drivers</h2>
                <p class="mt-1 text-sm text-gray-500">Add delivery people and approve them before assigning delivery jobs.</p>
            </div>
            <a href="{{ route('admin.drivers.create') }}" class="rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Add driver</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
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
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-2">
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
</x-admin-layout>
