@props([
    'schools',
    'inputClass' => 'mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600',
    'feeCardClass' => 'rounded-md border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-950',
    'feeLabel' => 'Selected school fee',
    'schoolHelp' => 'Select a school to show the destination fee.',
    'pickupHelp' => 'Pickup from the EduKit warehouse has no delivery fee.',
])

<div>
    <label for="delivery_preference" class="text-sm font-bold text-slate-700">Delivery option</label>
    <select id="delivery_preference" name="delivery_preference" required data-delivery-preference class="{{ $inputClass }}">
        <option value="school" @selected(old('delivery_preference', 'school') === 'school')>Deliver to school</option>
        <option value="pickup" @selected(old('delivery_preference') === 'pickup')>Pickup from warehouse</option>
    </select>
    @error('delivery_preference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div data-school-fields>
    <label for="school_id" class="text-sm font-bold text-slate-700">School</label>
    <select id="school_id" name="school_id" data-school-select class="{{ $inputClass }}">
        <option value="" data-fee="0">Select school</option>
        @foreach ($schools as $school)
            <option value="{{ $school->id }}" data-fee="{{ $school->delivery_fee }}" data-location="{{ $school->location }}" data-district="{{ $school->district?->name }}" @selected((int) old('school_id') === $school->id)>
                {{ $school->name }} - {{ $school->district?->name }}
            </option>
        @endforeach
    </select>
    @error('school_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div data-school-fields>
    <label for="learner_name" class="text-sm font-bold text-slate-700">Student name</label>
    <input id="learner_name" name="learner_name" value="{{ old('learner_name') }}" data-require-school-delivery class="{{ $inputClass }}">
    @error('learner_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div data-school-fields>
    <label for="class_level" class="text-sm font-bold text-slate-700">Class / stream</label>
    <input id="class_level" name="class_level" value="{{ old('class_level') }}" data-require-school-delivery class="{{ $inputClass }}">
    @error('class_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div class="sm:col-span-2" data-school-fee-card>
    <div class="{{ $feeCardClass }}">
        <p class="font-black">{{ $feeLabel }}: <span data-school-fee-text>UGX 0</span></p>
        <p class="mt-1 text-xs leading-5 text-emerald-800" data-school-location-text data-school-help="{{ $schoolHelp }}" data-pickup-help="{{ $pickupHelp }}">{{ $schoolHelp }}</p>
    </div>
</div>
