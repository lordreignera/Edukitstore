<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Add Driver</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.drivers.store') }}" class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-gray-700" for="name">Driver name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
                        <label class="text-sm font-medium text-gray-700" for="district">Operating district</label>
                        <input id="district" name="district" value="{{ old('district') }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="vehicle_type">Vehicle type</label>
                        <input id="vehicle_type" name="vehicle_type" value="{{ old('vehicle_type') }}" placeholder="Motorcycle, van, car" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="vehicle_registration">Vehicle registration</label>
                        <input id="vehicle_registration" name="vehicle_registration" value="{{ old('vehicle_registration') }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="payment_phone">Payout mobile money phone</label>
                        <input id="payment_phone" name="payment_phone" value="{{ old('payment_phone') }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                </div>
                <div class="mt-8 flex items-center gap-3">
                    <button class="rounded bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save driver</button>
                    <a href="{{ route('admin.drivers.index') }}" class="rounded border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
