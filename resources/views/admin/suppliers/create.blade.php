<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Add Supplier</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.suppliers.store') }}" class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-gray-700" for="business_name">Business name</label>
                        <input id="business_name" name="business_name" value="{{ old('business_name') }}" required class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('business_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="contact_person">Contact person</label>
                        <input id="contact_person" name="contact_person" value="{{ old('contact_person') }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="phone">Phone</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="district">District</label>
                        <input id="district" name="district" value="{{ old('district') }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-gray-700" for="address">Address</label>
                        <input id="address" name="address" value="{{ old('address') }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-gray-700" for="product_categories">Product categories supplied</label>
                        <textarea id="product_categories" name="product_categories" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Books, stationery, uniforms, bags, toiletries...">{{ old('product_categories') }}</textarea>
                        @error('product_categories') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="supply_capacity">Supply capacity</label>
                        <input id="supply_capacity" name="supply_capacity" value="{{ old('supply_capacity') }}" placeholder="Example: 200 orders per week" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('supply_capacity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-gray-700" for="notes">Internal notes</label>
                        <textarea id="notes" name="notes" rows="4" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('notes') }}</textarea>
                        @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-8 flex items-center gap-3">
                    <button class="rounded bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save supplier</button>
                    <a href="{{ route('admin.suppliers.index') }}" class="rounded border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
