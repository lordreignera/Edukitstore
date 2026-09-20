@props([
    'districts',
    'name' => 'district',
    'id' => 'district',
    'label' => 'District',
    'value' => null,
])

<div class="relative" data-district-select>
    <label class="text-sm font-bold text-slate-700" for="{{ $id }}">{{ $label }}</label>
    <div class="relative mt-1">
        <input id="{{ $id }}" name="{{ $name }}" value="{{ old($name, $value) }}" type="text" required autocomplete="off" placeholder="Search and select a district" data-district-input class="w-full rounded border-slate-300 pr-11 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        <button type="button" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-500 hover:text-[#07215f]" aria-label="Show districts" aria-expanded="false" data-district-toggle><x-ui.icon name="chevron-down" size="size-4" /></button>
    </div>

    <div hidden data-district-menu class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-slate-200 bg-white p-1 shadow-xl">
        @foreach ($districts as $district)
            <button type="button" data-district-option data-district-name="{{ $district->name }}" class="flex w-full items-center rounded px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">{{ $district->name }}</button>
        @endforeach
        <p hidden data-district-empty class="px-3 py-3 text-sm text-slate-500">No district matches your search.</p>
    </div>
    @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
