@php
    $prefix = $idPrefix ?? 'school';
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-name">School name</label>
        <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $school->name) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-district">District</label>
        <select id="{{ $prefix }}-district" name="district_id" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            <option value="">Select district</option>
            @foreach ($districts as $district)
                <option value="{{ $district->id }}" @selected((int) old('district_id', $school->district_id) === $district->id)>{{ $district->name }}</option>
            @endforeach
        </select>
        @error('district_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-school-code">School code</label>
        <input id="{{ $prefix }}-school-code" name="school_code" value="{{ old('school_code', $school->school_code) }}" placeholder="Optional EMIS/client code" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('school_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-location">Location / branch</label>
        <input id="{{ $prefix }}-location" name="location" value="{{ old('location', $school->location) }}" placeholder="Town, parish, road or campus notes" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('location') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-distance">Distance from warehouse (km)</label>
        <input id="{{ $prefix }}-distance" name="distance_from_warehouse_km" type="number" min="0" step="0.01" value="{{ old('distance_from_warehouse_km', $school->distance_from_warehouse_km) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('distance_from_warehouse_km') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-fee">Convenience / delivery fee (UGX)</label>
        <input id="{{ $prefix }}-fee" name="delivery_fee" type="number" min="0" step="1" value="{{ old('delivery_fee', $school->delivery_fee) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('delivery_fee') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-contact-person">School contact person</label>
        <input id="{{ $prefix }}-contact-person" name="contact_person" value="{{ old('contact_person', $school->contact_person) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('contact_person') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-contact-phone">Contact phone</label>
        <input id="{{ $prefix }}-contact-phone" name="contact_phone" value="{{ old('contact_phone', $school->contact_phone) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('contact_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-contact-email">Contact email</label>
        <input id="{{ $prefix }}-contact-email" name="contact_email" type="email" value="{{ old('contact_email', $school->contact_email) }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('contact_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="text-sm font-bold text-slate-700" for="{{ $prefix }}-notes">Notes</label>
        <textarea id="{{ $prefix }}-notes" name="notes" rows="4" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('notes', $school->notes) }}</textarea>
        @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-3 sm:col-span-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $school->is_active)) class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
        <span class="text-sm font-bold text-slate-700">Show this school at checkout</span>
    </label>
</div>
