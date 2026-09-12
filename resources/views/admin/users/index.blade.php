<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div><p class="text-sm font-bold text-emerald-700">Access control</p><h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Users &amp; Roles</h1><p class="mt-1 text-sm text-slate-500">Create accounts, assign roles and control sign-in access.</p></div>
            <a href="{{ route('admin.users.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-[#07215f] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0b2f7c]"><x-ui.icon name="plus" size="size-4" /> Add user</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-5 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-5 border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
            @endif

            <form method="GET" class="mb-5 grid gap-3 border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(220px,1fr)_240px_auto]">
                <label class="relative"><span class="sr-only">Search users</span><x-ui.icon name="search" size="size-4" class="absolute left-3 top-3 text-slate-400" /><input name="q" value="{{ $search }}" placeholder="Search by name or email" class="h-10 w-full rounded border-slate-300 pl-9 text-sm focus:border-emerald-600 focus:ring-emerald-600"></label>
                <select name="role" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">All roles</option>
                    @foreach ($roles as $value => $label)<option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>@endforeach
                </select>
                <button class="h-10 rounded-md bg-emerald-600 px-5 text-sm font-bold text-white hover:bg-emerald-700">Filter</button>
            </form>

            <section class="border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-500">
                            <tr><th class="px-5 py-3">User</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Access</th><th class="px-5 py-3">Last sign in</th><th class="px-5 py-3 text-right">Actions</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($users as $user)
                                @php($canManageUser = auth()->user()->hasRole('super-admin') || ! $user->hasAnyRole(['super-admin', 'admin', 'finance-admin']))
                                <tr>
                                    <td class="px-5 py-4"><p class="font-bold text-slate-900">{{ $user->name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $user->email }}</p>@if ($user->creator)<p class="mt-1 text-[11px] text-slate-400">Created by {{ $user->creator->name }}</p>@endif</td>
                                    <td class="px-5 py-4"><span class="inline-flex rounded bg-blue-50 px-2 py-1 text-xs font-bold capitalize text-blue-700">{{ str($user->getRoleNames()->first() ?? 'unassigned')->replace('-', ' ') }}</span></td>
                                    <td class="px-5 py-4"><span class="inline-flex rounded px-2 py-1 text-xs font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td class="px-5 py-4">
                                        @if ($canManageUser)
                                        <div class="flex min-w-max justify-end gap-2">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Edit</a>
                                            <form method="POST" action="{{ route('admin.users.password-link', $user) }}">@csrf<button class="rounded border border-emerald-300 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-50">Send password link</button></form>
                                            @unless ($user->is(auth()->user()))
                                                <form method="POST" action="{{ route('admin.users.status', $user) }}">@csrf @method('PATCH')<button class="rounded border px-3 py-1.5 text-xs font-bold {{ $user->is_active ? 'border-red-200 text-red-700 hover:bg-red-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                                            @endunless
                                        </div>
                                        @else
                                            <p class="text-right text-xs font-semibold text-slate-400">Super admin only</p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No user accounts match this filter.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
            </section>
        </div>
    </div>
</x-admin-layout>
