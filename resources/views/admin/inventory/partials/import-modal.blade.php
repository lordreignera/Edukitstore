<x-admin-modal name="import-inventory" title="Import products and stock" description="Upload a CSV with product details plus opening stock or new stock intake rows.">
    <form method="POST" action="{{ route('admin.inventory.import') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div class="rounded-md border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-950">
            <p class="font-bold">CSV columns</p>
            <p class="mt-1 text-xs font-semibold text-blue-800">Use the template for products, prices, stock date, warehouse quantity, display quantity and notes. Set source to opening_stock for launch balances or stock_intake for new buying.</p>
        </div>
        <div>
            <label class="text-sm font-bold text-slate-700" for="inventory-csv">CSV file</label>
            <input id="inventory-csv" name="inventory_csv" type="file" accept=".csv,text/csv,text/plain" required class="mt-2 block w-full text-sm text-slate-700 file:mr-4 file:rounded file:border-0 file:bg-emerald-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-800">
            @error('inventory_csv') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-wrap gap-3">
            <button class="rounded bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">Upload CSV</button>
            <a href="{{ route('admin.inventory.template') }}" class="rounded border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Download template</a>
            <button type="button" class="rounded border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50" @click="$dispatch('close-admin-modal')">Cancel</button>
        </div>
    </form>
</x-admin-modal>
