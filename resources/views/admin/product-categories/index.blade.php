<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold text-emerald-700">Catalogue Setup</p>
                <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Product Categories</h1>
            </div>
            <a href="{{ route('admin.product-categories.create') }}" class="inline-flex items-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">
                <x-ui.icon name="plus" size="size-4" /> Add category
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" class="mb-5 grid gap-3 border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(180px,1fr)_180px_auto_auto]">
                <input name="q" value="{{ $search }}" placeholder="Search category name or description" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                <select name="status" class="h-10 rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="">Any status</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="hidden" @selected($status === 'hidden')>Hidden</option>
                </select>
                <button class="h-10 rounded bg-[#07215f] px-4 text-sm font-bold text-white hover:bg-[#0b2f7c]">Filter</button>
                <a href="{{ route('admin.product-categories.index') }}" class="grid h-10 place-items-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Clear</a>
            </form>

            <section class="rounded border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    @if (session('status'))
                        <p class="text-sm font-medium text-emerald-700">{{ session('status') }}</p>
                    @else
                        <p class="text-sm text-slate-500">Manage the catalogue groups used by products in the admin and public shop.</p>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Category</th>
                                <th class="px-5 py-3">Slug</th>
                                <th class="px-5 py-3">Products</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-950">{{ $category->name }}</p>
                                        <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $category->description ?: 'No description' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ $category->slug }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ number_format($category->products_count) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded px-2 py-1 text-xs font-bold {{ $category->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $category->is_active ? 'Active' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.product-categories.show', $category) }}" class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700" title="View category" aria-label="View {{ $category->name }}">
                                                <x-ui.icon name="eye" size="size-4" />
                                            </a>
                                            <a href="{{ route('admin.product-categories.edit', $category) }}" class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" title="Edit category" aria-label="Edit {{ $category->name }}">
                                                <x-ui.icon name="edit" size="size-4" />
                                            </a>
                                            <form method="POST" action="{{ route('admin.product-categories.destroy', $category) }}" onsubmit="return confirm('Delete this category? Products in it will become uncategorised.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="grid size-9 place-items-center rounded border border-slate-200 text-slate-600 hover:border-red-200 hover:bg-red-50 hover:text-red-700" title="Delete category" aria-label="Delete {{ $category->name }}">
                                                    <x-ui.icon name="trash" size="size-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-slate-500">No categories have been added.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $categories->links() }}
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
