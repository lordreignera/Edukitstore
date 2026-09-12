<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-bold text-emerald-700">Product Categories</p>
            <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Add Category</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.product-categories.store') }}" class="rounded border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                @include('admin.product-categories._form', ['submitLabel' => 'Save category'])
            </form>
        </div>
    </div>
</x-admin-layout>
