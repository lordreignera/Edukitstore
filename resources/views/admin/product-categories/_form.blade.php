@csrf

<div class="grid gap-5">
    <div>
        <label class="text-sm font-bold text-slate-700" for="name">Category name</label>
        <input id="name" name="name" value="{{ old('name', $category->name) }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-bold text-slate-700" for="description">Description</label>
        <textarea id="description" name="description" rows="4" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('description', $category->description) }}</textarea>
        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-start gap-3 border border-slate-200 bg-slate-50 p-4">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $category->exists ? $category->is_active : true)) class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
        <span>
            <span class="block text-sm font-bold text-slate-800">Active category</span>
            <span class="mt-1 block text-xs leading-5 text-slate-500">Active categories can be selected for products and shown on the website catalogue.</span>
        </span>
    </label>
</div>

<div class="mt-7 flex flex-wrap items-center gap-3">
    <button class="inline-flex min-h-11 items-center rounded-md bg-[#07215f] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0b2f7c]">{{ $submitLabel }}</button>
    <a href="{{ route('admin.product-categories.index') }}" class="inline-flex min-h-11 items-center rounded-md border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>
