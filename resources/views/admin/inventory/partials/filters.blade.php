<form method="GET" class="grid gap-3 rounded-md border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_190px_190px_160px_160px_auto_auto]">
    <input name="q" value="{{ $search }}" placeholder="Search product, code or brand" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
    <select name="category" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        <option value="">All categories</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected($categoryId === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <select name="stock_status" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
        <option value="">Any stock status</option>
        <option value="ready" @selected($stockStatus === 'ready')>Ready on website</option>
        <option value="low_display" @selected($stockStatus === 'low_display')>Low display stock</option>
        <option value="out_of_display" @selected($stockStatus === 'out_of_display')>Out of display stock</option>
        <option value="warehouse_empty" @selected($stockStatus === 'warehouse_empty')>Warehouse empty</option>
    </select>
    <input name="from" type="date" value="{{ $from }}" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" aria-label="From date">
    <input name="to" type="date" value="{{ $to }}" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" aria-label="To date">
    <button class="h-10 rounded bg-[#07215f] px-5 text-sm font-bold text-white hover:bg-[#0b2f7c]">Filter</button>
    <a href="{{ route('admin.inventory.index') }}" class="grid h-10 place-items-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Clear</a>
</form>
