<x-admin-layout>
    <x-slot name="header"><div><p class="text-sm font-bold text-orange-700">User management / Roles</p><h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Edit role</h1><p class="mt-1 text-sm text-slate-500">Update the permissions inherited by users assigned to this role.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8"><form method="POST" action="{{ route('admin.roles.update', $role) }}" class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">@csrf @method('PUT') @include('admin.roles._form', ['submitLabel' => 'Save role'])</form></div></div>
</x-admin-layout>
