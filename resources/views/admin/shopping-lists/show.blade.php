<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Review Shopping List</h2>
                <p class="mt-1 text-sm text-gray-500">Uploaded by {{ $shoppingList->parent_name }} on {{ $shoppingList->created_at->format('M d, Y') }}.</p>
            </div>
            <a href="{{ route('admin.shopping-lists.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back to lists</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
            <section class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                @if (session('status'))
                    <div class="mb-5 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <h3 class="text-lg font-bold text-gray-950">Customer Details</h3>
                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold text-gray-500">Parent</dt>
                        <dd class="mt-1 text-gray-950">{{ $shoppingList->parent_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-500">Phone</dt>
                        <dd class="mt-1 text-gray-950">{{ $shoppingList->phone }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-500">Email</dt>
                        <dd class="mt-1 text-gray-950">{{ $shoppingList->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-500">School</dt>
                        <dd class="mt-1 text-gray-950">{{ $shoppingList->school_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-500">Learner</dt>
                        <dd class="mt-1 text-gray-950">{{ $shoppingList->learner_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-500">Class</dt>
                        <dd class="mt-1 text-gray-950">{{ $shoppingList->class_level ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-500">Delivery</dt>
                        <dd class="mt-1 text-gray-950">{{ ucfirst($shoppingList->delivery_preference) }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-500">Location</dt>
                        <dd class="mt-1 text-gray-950">{{ $shoppingList->delivery_location ?? '-' }}</dd>
                    </div>
                </dl>

                <div class="mt-6 border-t border-gray-100 pt-5">
                    <p class="text-sm font-semibold text-gray-500">Notes</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $shoppingList->notes ?: 'No extra notes.' }}</p>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-5">
                    <p class="text-sm font-semibold text-gray-500">Uploaded file</p>
                    <a href="{{ route('admin.shopping-lists.download', $shoppingList) }}" class="mt-2 inline-flex rounded bg-[#07215f] px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                        Download {{ $shoppingList->original_filename }}
                    </a>
                </div>
            </section>

            <section class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-gray-950">Review Status</h3>
                <form method="POST" action="{{ route('admin.shopping-lists.update', $shoppingList) }}" class="mt-5 space-y-5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="text-sm font-medium text-gray-700" for="status">Status</label>
                        <select id="status" name="status" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $shoppingList->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700" for="estimated_total">Estimated total (UGX)</label>
                        <input id="estimated_total" name="estimated_total" type="number" min="0" step="1" value="{{ old('estimated_total', $shoppingList->estimated_total) }}" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('estimated_total') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button class="rounded bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save review</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
