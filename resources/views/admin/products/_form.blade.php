@csrf
@php($prefix = $idPrefix ?? 'product')

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="text-sm font-medium text-gray-700" for="{{ $prefix }}-name">Product name</label>
        <input id="{{ $prefix }}-name" name="name" value="{{ old('name', $product->name) }}" required class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="{{ $prefix }}-sku">Product code</label>
        <input id="{{ $prefix }}-sku" value="{{ $product->exists ? $product->sku : 'Generated after save' }}" disabled class="mt-1 w-full rounded border-gray-300 bg-gray-50 text-sm font-semibold text-gray-600">
        <p class="mt-1 text-xs text-gray-500">Auto-generated as EduKit code, year/month and sequence.</p>
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="{{ $prefix }}-category">Category</label>
        <select id="{{ $prefix }}-category" name="product_category_id" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            <option value="">Uncategorised</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('product_category_id', $product->product_category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('product_category_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="{{ $prefix }}-price">Customer price (UGX)</label>
        <input id="{{ $prefix }}-price" name="price" type="number" min="0" step="1" value="{{ old('price', $product->price) }}" required class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="{{ $prefix }}-stock">Available stock</label>
        <input id="{{ $prefix }}-stock" name="stock_quantity" type="number" min="0" step="1" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('stock_quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="{{ $prefix }}-brand">Brand</label>
        <input id="{{ $prefix }}-brand" name="brand" value="{{ old('brand', $product->brand) }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('brand') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="{{ $prefix }}-unit">Unit</label>
        <input id="{{ $prefix }}-unit" name="unit" value="{{ old('unit', $product->unit) }}" placeholder="Piece, pack, pair" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="text-sm font-medium text-gray-700" for="{{ $prefix }}-image">Product image</label>
        @if ($product->image_url)
            <div class="mt-2 flex items-center gap-4 rounded border border-gray-200 bg-gray-50 p-3">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="size-20 rounded bg-white object-contain">
                <p class="text-xs leading-5 text-gray-500">Upload a new image only when you want to replace the current one.</p>
            </div>
        @endif
        <input id="{{ $prefix }}-image" name="image" type="file" accept="image/png,image/jpeg,image/webp" @required(! $product->exists) class="mt-2 block w-full text-sm text-gray-700 file:mr-4 file:rounded file:border-0 file:bg-emerald-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-800">
        @error('image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="text-sm font-medium text-gray-700" for="{{ $prefix }}-description">Description</label>
        <textarea id="{{ $prefix }}-description" name="description" rows="4" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('description', $product->description) }}</textarea>
        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-4">
    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->exists ? $product->is_active : true)) class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">
        Active on website
    </label>
    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
        <input name="is_featured" type="checkbox" value="1" @checked(old('is_featured', $product->is_featured)) class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">
        Featured
    </label>
</div>

<div class="mt-8 flex items-center gap-3">
    <button class="rounded bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save product</button>
    @if ($modal ?? false)
        <button type="button" class="rounded border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="$dispatch('close-admin-modal')">Cancel</button>
    @else
        <a href="{{ route('admin.products.index') }}" class="rounded border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
    @endif
</div>
