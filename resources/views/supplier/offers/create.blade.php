@php($navigation = config('portals.supplier_navigation'))
<x-portal-layout title="Submit Product" portal-name="Supplier Portal" :navigation="$navigation">
    <x-slot name="header"><div><h1 class="text-2xl font-extrabold text-[#071d4f]">Submit product and stock</h1><p class="mt-1 text-sm text-slate-500">Choose an EduKit product or propose a genuinely new item for review.</p></div></x-slot>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('supplier.offers.store') }}" enctype="multipart/form-data" class="mx-auto max-w-4xl rounded-md border border-slate-200 bg-white p-5 shadow-sm sm:p-7" data-product-offer-form>
            @csrf
            @if($errors->any())<div class="mb-5 rounded-md border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>@endif

            <fieldset>
                <legend class="text-sm font-extrabold text-[#071d4f]">Product identity</legend>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer gap-3 rounded-md border border-slate-200 p-4"><input type="radio" name="product_mode" value="existing" class="mt-1 text-emerald-700" @checked(old('product_mode', 'existing') === 'existing')><span><strong class="block text-sm">Use master product</strong><span class="text-xs text-slate-500">Use EduKit's approved name, category and image.</span></span></label>
                    <label class="flex cursor-pointer gap-3 rounded-md border border-slate-200 p-4"><input type="radio" name="product_mode" value="new" class="mt-1 text-emerald-700" @checked(old('product_mode') === 'new')><span><strong class="block text-sm">Propose new product</strong><span class="text-xs text-slate-500">For a genuinely different bag, shoe, brand or item.</span></span></label>
                </div>
            </fieldset>

            <div class="mt-5" data-existing-fields>
                <label for="product_id" class="text-sm font-bold">Master product</label>
                <select id="product_id" name="product_id" class="mt-1 w-full rounded-md border-slate-300 text-sm">
                    <option value="">Search and select a product</option>
                    @foreach($products as $product)<option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} ({{ $product->sku }})</option>@endforeach
                </select>
                <p class="mt-2 text-xs text-slate-500">Already supplying this product? Use Restock from My Products instead.</p>
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2" data-new-fields>
                <div class="sm:col-span-2"><label class="text-sm font-bold">Proposed product name</label><input name="submitted_name" value="{{ old('submitted_name') }}" class="mt-1 w-full rounded-md border-slate-300"></div>
                <div><label class="text-sm font-bold">Category</label><select name="product_category_id" class="mt-1 w-full rounded-md border-slate-300"><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('product_category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div><label class="text-sm font-bold">Brand</label><input name="submitted_brand" value="{{ old('submitted_brand') }}" class="mt-1 w-full rounded-md border-slate-300"></div>
                <div><label class="text-sm font-bold">Unit</label><input name="submitted_unit" value="{{ old('submitted_unit') }}" placeholder="Each, dozen, pack" class="mt-1 w-full rounded-md border-slate-300"></div>
                <div><label class="text-sm font-bold">Clear product image</label><input name="image" type="file" accept=".jpg,.jpeg,.png,.webp" class="mt-1 w-full rounded-md border border-slate-300 p-2 text-sm"><p class="mt-1 text-xs text-slate-500">Minimum 500 x 500 pixels. Use a clear, well-lit product photo.</p></div>
                <div class="sm:col-span-2"><label class="text-sm font-bold">Description</label><textarea name="submitted_description" rows="4" class="mt-1 w-full rounded-md border-slate-300">{{ old('submitted_description') }}</textarea></div>
            </div>

            <div class="mt-6 grid gap-5 border-t border-slate-200 pt-5 sm:grid-cols-3">
                <div><label class="text-sm font-bold">Available quantity</label><input name="quantity_submitted" type="number" min="1" value="{{ old('quantity_submitted') }}" required class="mt-1 w-full rounded-md border-slate-300"></div>
                <div><label class="text-sm font-bold">Your price per unit (UGX)</label><input name="supplier_price" type="number" min="1" value="{{ old('supplier_price') }}" required class="mt-1 w-full rounded-md border-slate-300"></div>
                <div><label class="text-sm font-bold">Low stock alert</label><input name="reorder_level" type="number" min="0" value="{{ old('reorder_level', 5) }}" required class="mt-1 w-full rounded-md border-slate-300"></div>
            </div>
            <div class="mt-6 flex justify-end gap-3"><a href="{{ route('supplier.offers.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold">Cancel</a><button class="rounded-md bg-emerald-700 px-5 py-2 text-sm font-bold text-white">Submit for approval</button></div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-product-offer-form]');
            if (!form) return;
            const sync = () => {
                const isNew = form.querySelector('[name="product_mode"]:checked')?.value === 'new';
                form.querySelector('[data-existing-fields]').classList.toggle('hidden', isNew);
                form.querySelector('[data-new-fields]').classList.toggle('hidden', !isNew);
                form.querySelector('[name="product_id"]').disabled = isNew;
                form.querySelectorAll('[data-new-fields] input, [data-new-fields] select, [data-new-fields] textarea').forEach(field => field.disabled = !isNew);
            };
            form.querySelectorAll('[name="product_mode"]').forEach(input => input.addEventListener('change', sync));
            sync();
        });
    </script>
</x-portal-layout>
