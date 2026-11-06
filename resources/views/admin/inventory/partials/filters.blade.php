<form method="GET" class="filter-bar">
    <input name="q" value="{{ $search }}" placeholder="Search product, code or brand" class="h-10 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
    <select name="category" class="h-10 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
        <option value="">All categories</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected($categoryId === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <select name="stock_status" class="h-10 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
        <option value="">Any stock status</option>
        <option value="ready" @selected($stockStatus === 'ready')>Ready on website</option>
        <option value="low_display" @selected($stockStatus === 'low_display')>Low display stock</option>
        <option value="out_of_display" @selected($stockStatus === 'out_of_display')>Out of display stock</option>
        <option value="warehouse_empty" @selected($stockStatus === 'warehouse_empty')>Warehouse empty</option>
    </select>
    <input name="from" type="date" value="{{ $from }}" class="h-10 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600" aria-label="From date">
    <input name="to" type="date" value="{{ $to }}" class="h-10 rounded border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600" aria-label="To date">
    <label class="flex min-h-10 items-center gap-2 text-xs font-semibold text-slate-700">
        <input type="checkbox" name="include_demo" value="1" @checked($includeDemo) class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
        Include demo payments
    </label>
    <button class="h-10 rounded bg-[#07215f] px-5 text-sm font-bold text-white hover:bg-[#0b2f7c]">Filter</button>
    <a href="{{ route('admin.inventory.index') }}" class="grid h-10 place-items-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Clear</a>
</form>
