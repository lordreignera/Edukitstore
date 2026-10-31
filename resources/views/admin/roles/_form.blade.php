@if ($errors->any())<div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Please correct the highlighted role details.</div>@endif

<div>
    <label for="name" class="text-sm font-bold text-slate-700">Role name</label>
    <input id="name" name="name" value="{{ old('name', $role->name) }}" required @readonly(in_array($role->name, ['super-admin', 'admin', 'finance-admin'], true)) class="mt-1 w-full rounded border-slate-300 text-sm lowercase focus:border-orange-600 focus:ring-orange-600">
    <p class="mt-1 text-xs text-slate-500">Use lowercase letters, numbers, dots, hyphens, or underscores. Example: warehouse-manager.</p>
    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<fieldset class="mt-7">
    <legend class="text-sm font-bold text-slate-700">Assigned permissions</legend>
    <p class="mt-1 text-xs text-slate-500">These permissions control which protected workspace modules this role can open.</p>
    @php($selected = old('permissions', $selectedPermissions))
    <div class="mt-4 space-y-5">
        @foreach ($permissions as $module => $modulePermissions)
            <div class="rounded-md border border-slate-200 p-4">
                <h2 class="text-sm font-extrabold text-[#071d4f]">{{ $module }}</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($modulePermissions as $permission)
                        @php($permissionId = 'permission_'.str_replace(['.', '-'], '_', $permission->name))
                        <label for="{{ $permissionId }}" class="flex cursor-pointer items-start gap-3 rounded border border-slate-200 px-3 py-3 transition hover:border-orange-300 hover:bg-orange-50">
                            <input id="{{ $permissionId }}" type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, (array) $selected, true)) class="mt-0.5 rounded border-slate-300 text-orange-600 focus:ring-orange-600">
                            <span><span class="block text-sm font-bold text-slate-800">{{ \App\Support\PermissionCatalog::label($permission->name) }}</span><span class="block text-xs text-slate-500">{{ $permission->name }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    @error('permissions')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    @error('permissions.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</fieldset>

<div class="mt-7 flex justify-end gap-3"><a href="{{ route('admin.roles.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a><button class="inline-flex min-h-11 items-center rounded-md bg-[#071d4f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-orange-700">{{ $submitLabel }}</button></div>
