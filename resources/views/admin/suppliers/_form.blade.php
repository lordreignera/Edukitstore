@php($prefix = $idPrefix ?? 'supplier')
<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-business-name">Business name</label>
        <input id="{{ $prefix }}-business-name" name="business_name" value="{{ old('business_name', $supplier->business_name) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('business_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-contact">Contact person</label>
        <input id="{{ $prefix }}-contact" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-phone">Phone</label>
        <input id="{{ $prefix }}-phone" name="phone" value="{{ old('phone', $supplier->phone) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-email">Email</label>
        <input id="{{ $prefix }}-email" name="email" type="email" value="{{ old('email', $supplier->email) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-district">District</label>
        <input id="{{ $prefix }}-district" name="district" value="{{ old('district', $supplier->district) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
    <div class="sm:col-span-2">
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-address">Address</label>
        <input id="{{ $prefix }}-address" name="address" value="{{ old('address', $supplier->address) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
    <div class="sm:col-span-2">
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-categories">Product categories</label>
        <textarea id="{{ $prefix }}-categories" name="product_categories" rows="3" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('product_categories', $supplier->product_categories) }}</textarea>
    </div>
    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-capacity">Supply capacity</label>
        <input id="{{ $prefix }}-capacity" name="supply_capacity" value="{{ old('supply_capacity', $supplier->supply_capacity) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    </div>
    <div class="sm:col-span-2">
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-notes">Internal notes</label>
        <textarea id="{{ $prefix }}-notes" name="notes" rows="3" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('notes', $supplier->notes) }}</textarea>
    </div>
</div>
<div class="mt-6 flex justify-end gap-3">
    <button type="button" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" @click="$dispatch('close-admin-modal')">Cancel</button>
    <button class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-700">{{ $submitLabel }}</button>
</div>
