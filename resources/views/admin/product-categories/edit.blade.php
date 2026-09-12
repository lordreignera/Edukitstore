<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-bold text-emerald-700">Product Categories</p>
            <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Edit Category</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.product-categories.update', $category) }}" class="rounded border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                @method('PUT')
                @include('admin.product-categories._form', ['submitLabel' => 'Save changes'])
            </form>

            <form method="POST" action="{{ route('admin.product-categories.destroy', $category) }}" class="mt-4" onsubmit="return confirm('Delete this category? Products in it will become uncategorised.')">
                @csrf
                @method('DELETE')
                <button class="inline-flex min-h-11 items-center gap-2 rounded-md border border-red-200 px-5 py-2.5 text-sm font-bold text-red-700 hover:bg-red-50">
                    <x-ui.icon name="trash" size="size-4" /> Delete category
                </button>
            </form>
        </div>
    </div>
</x-admin-layout>
