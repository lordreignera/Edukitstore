<x-admin-layout>
    <x-slot name="header">
        <div><p class="text-sm font-bold text-emerald-700">Users &amp; Roles</p><h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Edit account</h1></div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                @csrf
                @method('PUT')
                @include('admin.users._form', ['submitLabel' => 'Save changes'])
            </form>
        </div>
    </div>
</x-admin-layout>
