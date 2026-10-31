<x-admin-layout>
    <x-slot name="header">
        <div><p class="text-sm font-bold text-orange-700">User management / Users</p><h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Create user account</h1></div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="mb-5 border-l-4 border-orange-500 bg-orange-50 px-4 py-3 text-sm leading-6 text-orange-950">Set a temporary password for this user. After signing in, they will be asked to change it from their profile.</div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                @csrf
                @include('admin.users._form', ['submitLabel' => 'Create account'])
            </form>
        </div>
    </div>
</x-admin-layout>
