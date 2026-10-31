<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div><p class="text-sm font-bold text-orange-700">User management</p><h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Roles</h1><p class="mt-1 text-sm text-slate-500">Create roles and decide which EduKit capabilities each role receives.</p></div>
            @can('roles.create')<a href="{{ route('admin.roles.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-[#07215f] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-orange-700"><x-ui.icon name="plus" size="size-4" /> Add role</a>@endcan
        </div>
    </x-slot>

    <div class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if (session('status'))<div class="mb-5 border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-bold text-orange-800">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-5 border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif

        <section class="overflow-hidden border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-500"><tr><th class="px-5 py-3">Role</th><th class="px-5 py-3">Permissions</th><th class="px-5 py-3">Users</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($roles as $role)
                        <tr>
                            <td class="px-5 py-4"><p class="font-bold text-slate-900">{{ str($role->name)->replace(['-', '_'], ' ')->title() }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $role->name }}</p></td>
                            <td class="px-5 py-4"><span class="rounded bg-orange-50 px-2 py-1 text-xs font-bold text-orange-800">{{ $role->permissions_count }} assigned</span></td>
                            <td class="px-5 py-4 text-slate-600">{{ $role->users_count }} users</td>
                            <td class="px-5 py-4"><div class="flex min-w-max justify-end gap-2"><a href="{{ route('admin.roles.edit', $role) }}" class="rounded border border-orange-200 px-3 py-1.5 text-xs font-bold text-orange-800 transition hover:bg-orange-50">Edit permissions</a>@unless (in_array($role->name, ['super-admin', 'admin', 'finance-admin'], true))<form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">@csrf @method('DELETE')<button class="rounded border border-red-200 px-3 py-1.5 text-xs font-bold text-red-700 transition hover:bg-red-50">Delete</button></form>@endunless</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No roles are available.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $roles->links() }}</div>
        </section>
    </div></div>
</x-admin-layout>
