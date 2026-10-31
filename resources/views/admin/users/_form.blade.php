@if ($errors->any())
    <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Please correct the highlighted account details.</div>
@endif

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="name" class="text-sm font-bold text-slate-700">Full name</label>
        <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="email" class="text-sm font-bold text-slate-700">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    @php($selectedRoles = old('roles', $user->exists ? $user->roles->pluck('name')->all() : []))
    <fieldset class="sm:col-span-2">
        <legend class="text-sm font-bold text-slate-700">Account roles</legend>
        <p class="mt-1 text-xs text-slate-500">Select one or more roles. Partner roles can only be assigned to approved linked accounts.</p>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            @foreach ($roles as $value => $label)
                @php($roleId = 'role_'.str_replace(['-', '.'], '_', $value))
                <label for="{{ $roleId }}" class="flex cursor-pointer items-start gap-3 rounded-md border border-slate-200 px-3 py-3 transition hover:border-orange-300 hover:bg-orange-50">
                    <input id="{{ $roleId }}" type="checkbox" name="roles[]" value="{{ $value }}" @checked(in_array($value, (array) $selectedRoles, true)) class="mt-0.5 rounded border-slate-300 text-orange-600 focus:ring-orange-600">
                    <span><span class="block text-sm font-bold text-slate-800">{{ $label }}</span><span class="block text-xs text-slate-500">{{ $value }}</span></span>
                </label>
            @endforeach
        </div>
        @error('roles') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        @error('roles.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    @unless ($user->exists)
        <div>
            <label for="password" class="text-sm font-bold text-slate-700">Temporary password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="text-sm font-bold text-slate-700">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
        </div>
        <div class="sm:col-span-2 border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
            Share this temporary password securely. The user will be sent to their profile to change it after signing in.
        </div>
    @endunless
    <label class="sm:col-span-2 flex items-start gap-3 border border-slate-200 bg-slate-50 p-4">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $user->is_active)) class="mt-0.5 rounded border-slate-300 text-orange-600 focus:ring-orange-600">
        <span><span class="block text-sm font-bold text-slate-800">Active account</span><span class="mt-1 block text-xs leading-5 text-slate-500">Only active accounts can sign in. Deactivation also ends existing web sessions.</span></span>
    </label>
</div>

<div class="mt-7 flex flex-wrap items-center gap-3">
    <button class="inline-flex min-h-11 items-center rounded-md bg-[#07215f] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0b2f7c]">{{ $submitLabel }}</button>
    <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>
