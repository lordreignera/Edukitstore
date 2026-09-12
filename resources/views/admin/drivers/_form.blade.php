@php($prefix = $idPrefix ?? 'driver')
<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-name">Full name</label>
        <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $driver->name) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-phone">Phone</label>
        <input id="{{ $prefix }}-phone" name="phone" value="{{ old('phone', $driver->phone) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-email">Email</label>
        <input id="{{ $prefix }}-email" name="email" type="email" value="{{ old('email', $driver->email) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-district">Operating district</label>
        <input id="{{ $prefix }}-district" name="district" value="{{ old('district', $driver->district) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-vehicle">Vehicle type</label>
        <input id="{{ $prefix }}-vehicle" name="vehicle_type" value="{{ old('vehicle_type', $driver->vehicle_type) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-registration">Vehicle registration</label>
        <input id="{{ $prefix }}-registration" name="vehicle_registration" value="{{ old('vehicle_registration', $driver->vehicle_registration) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-payment">Payout phone</label>
        <input id="{{ $prefix }}-payment" name="payment_phone" value="{{ old('payment_phone', $driver->payment_phone) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
</div>
<div class="mt-6 flex justify-end gap-3">
    <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" @click="$dispatch('close-admin-modal')">Cancel</button>
    <button class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-700">{{ $submitLabel }}</button>
</div>
