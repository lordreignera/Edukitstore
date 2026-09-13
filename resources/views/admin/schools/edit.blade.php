<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold text-emerald-700">Delivery Setup</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Edit School</h1>
            </div>
            <a href="{{ route('admin.schools.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back to schools</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <section class="rounded border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('admin.schools.update', $school) }}">
                    @csrf
                    @method('PATCH')
                    @include('admin.schools._form', ['school' => $school, 'districts' => $districts])

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('admin.schools.index') }}" class="rounded border border-slate-300 px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</a>
                        <button class="rounded bg-emerald-700 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-800">Update school</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-admin-layout>
