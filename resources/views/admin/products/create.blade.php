<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Add Product</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                @include('admin.products._form')
            </form>
        </div>
    </div>
</x-admin-layout>
