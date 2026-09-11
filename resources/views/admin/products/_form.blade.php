@csrf

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="text-sm font-medium text-gray-700" for="name">Product name</label>
        <input id="name" name="name" value="{{ old('name', $product->name) }}" required class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="sku">SKU</label>
        <input id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="product_category_id">Category</label>
        <select id="product_category_id" name="product_category_id" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
            <option value="">Uncategorised</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('product_category_id', $product->product_category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('product_category_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="price">Customer price (UGX)</label>
        <input id="price" name="price" type="number" min="0" step="1" value="{{ old('price', $product->price) }}" required class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="stock_quantity">Available stock</label>
        <input id="stock_quantity" name="stock_quantity" type="number" min="0" step="1" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('stock_quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="brand">Brand</label>
        <input id="brand" name="brand" value="{{ old('brand', $product->brand) }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('brand') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="text-sm font-medium text-gray-700" for="unit">Unit</label>
        <input id="unit" name="unit" value="{{ old('unit', $product->unit) }}" placeholder="Piece, pack, pair" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="text-sm font-medium text-gray-700" for="image_url">Image URL</label>
        <input id="image_url" name="image_url" value="{{ old('image_url', $product->image_url) }}" placeholder="/images/products/example.jpg" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        @error('image_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="text-sm font-medium text-gray-700" for="description">Description</label>
        <textarea id="description" name="description" rows="4" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('description', $product->description) }}</textarea>
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
    <a href="{{ route('admin.products.index') }}" class="rounded border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
</div>
