<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Admin Dashboard</h2>
            <p class="text-sm text-gray-500">Live operating view for the EduKit master catalogue, suppliers and drivers.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                @foreach ([
                    'Products' => $stats['products'],
                    'Active Products' => $stats['active_products'],
                    'Pending Suppliers' => $stats['pending_suppliers'],
                    'Approved Suppliers' => $stats['approved_suppliers'],
                    'Pending Lists' => $stats['pending_shopping_lists'],
                    'All Lists' => $stats['shopping_lists'],
                    'Pending Drivers' => $stats['pending_drivers'],
                    'Approved Drivers' => $stats['approved_drivers'],
                ] as $label => $value)
                    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                        <p class="mt-3 text-3xl font-bold text-gray-950">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-4">
                <section class="rounded border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-950">Latest Products</h3>
                        <a href="{{ route('admin.products.index') }}" class="text-sm font-semibold text-emerald-700">Manage</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($latestProducts as $product)
                            <div class="px-5 py-4">
                                <p class="font-semibold text-gray-950">{{ $product->name }}</p>
                                <p class="mt-1 text-sm text-gray-500">UGX {{ number_format($product->price) }} - {{ $product->category?->name ?? 'Uncategorised' }}</p>
                            </div>
                        @empty
                            <p class="px-5 py-6 text-sm text-gray-500">No products yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-950">Shopping Lists</h3>
                        <a href="{{ route('admin.shopping-lists.index') }}" class="text-sm font-semibold text-emerald-700">Review</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($latestShoppingLists as $shoppingList)
                            <div class="px-5 py-4">
                                <p class="font-semibold text-gray-950">{{ $shoppingList->parent_name }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $shoppingList->school_name ?? 'School pending' }} - {{ ucfirst($shoppingList->status) }}</p>
                            </div>
                        @empty
                            <p class="px-5 py-6 text-sm text-gray-500">No shopping lists uploaded yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-950">Suppliers</h3>
                        <a href="{{ route('admin.suppliers.index') }}" class="text-sm font-semibold text-emerald-700">Review</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($latestSuppliers as $supplier)
                            <div class="px-5 py-4">
                                <p class="font-semibold text-gray-950">{{ $supplier->business_name }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $supplier->district ?? 'No district' }} - {{ $supplier->is_approved ? 'Approved' : 'Pending' }}</p>
                            </div>
                        @empty
                            <p class="px-5 py-6 text-sm text-gray-500">No suppliers yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-950">Drivers</h3>
                        <a href="{{ route('admin.drivers.index') }}" class="text-sm font-semibold text-emerald-700">Review</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($latestDrivers as $driver)
                            <div class="px-5 py-4">
                                <p class="font-semibold text-gray-950">{{ $driver->name }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $driver->vehicle_type ?? 'Vehicle pending' }} - {{ $driver->is_approved ? 'Approved' : 'Pending' }}</p>
                            </div>
                        @empty
                            <p class="px-5 py-6 text-sm text-gray-500">No drivers yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
