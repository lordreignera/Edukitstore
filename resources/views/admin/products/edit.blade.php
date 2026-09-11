<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Product</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.products.update', $product) }}" class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                @method('PUT')
                @include('admin.products._form')
            </form>

            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="mt-4">
                @csrf
                @method('DELETE')
                <button class="text-sm font-semibold text-red-700 hover:text-red-900">Delete product</button>
            </form>
        </div>
    </div>
</x-app-layout>
