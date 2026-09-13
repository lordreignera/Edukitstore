<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold text-emerald-700">Delivery Setup</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Schools</h1>
                <p class="mt-1 text-sm text-slate-500">Set school locations, contacts, warehouse distance and delivery fees used at checkout.</p>
            </div>
            <a href="{{ route('admin.schools.create') }}" class="inline-flex items-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">
                <x-ui.icon name="plus" size="size-4" /> Add school
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" class="mb-5 grid gap-3 border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[minmax(180px,1fr)_220px_160px_auto_auto]">
                <input name="q" value="{{ $search }}" placeholder="Search school, contact, code or location" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                <select name="district_id" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">All districts</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected((string) $district->id === $selectedDistrictId)>{{ $district->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">Any status</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="hidden" @selected($status === 'hidden')>Hidden</option>
                </select>
                <button class="h-10 rounded bg-[#07215f] px-4 text-sm font-bold text-white hover:bg-[#0b2f7c]">Filter</button>
                <a href="{{ route('admin.schools.index') }}" class="grid h-10 place-items-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Clear</a>
            </form>

            <section class="rounded border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    @if (session('status'))
                        <p class="text-sm font-medium text-emerald-700">{{ session('status') }}</p>
                    @else
                        <p class="text-sm text-slate-500">Active schools appear in the checkout school selector.</p>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">School</th>
                                <th class="px-5 py-3">District</th>
                                <th class="px-5 py-3">Distance</th>
                                <th class="px-5 py-3">Delivery fee</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($schools as $school)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-950">{{ $school->name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $school->location ?: 'Location pending' }}{{ $school->contact_person ? ' | '.$school->contact_person : '' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ $school->district?->name ?? '-' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ number_format((float) $school->distance_from_warehouse_km, 2) }} km</td>
                                    <td class="px-5 py-4 font-black text-slate-950">UGX {{ number_format($school->delivery_fee) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded px-2 py-1 text-xs font-bold {{ $school->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $school->is_active ? 'Active' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.schools.show', $school) }}" class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700" title="View school" aria-label="View {{ $school->name }}">
                                                <x-ui.icon name="eye" size="size-4" />
                                            </a>
                                            <a href="{{ route('admin.schools.edit', $school) }}" class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" title="Edit school" aria-label="Edit {{ $school->name }}">
                                                <x-ui.icon name="edit" size="size-4" />
                                            </a>
                                            <form method="POST" action="{{ route('admin.schools.destroy', $school) }}" onsubmit="return confirm('Delete this school? Existing invoices keep the saved school name.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-red-200 hover:bg-red-50 hover:text-red-700" title="Delete school" aria-label="Delete {{ $school->name }}">
                                                    <x-ui.icon name="trash" size="size-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">No schools have been added.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $schools->links() }}
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
