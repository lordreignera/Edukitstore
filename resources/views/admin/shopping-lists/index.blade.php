<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Shopping Lists</h2>
            <p class="text-sm text-gray-500">Review uploaded school lists and prepare quotes before fulfilment.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    @if (session('status'))
                        <p class="text-sm font-medium text-emerald-700">{{ session('status') }}</p>
                    @else
                        <p class="text-sm text-gray-500">Pending uploads should be reviewed against the master product catalogue.</p>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Parent</th>
                                <th class="px-5 py-3">School</th>
                                <th class="px-5 py-3">Delivery</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Estimate</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($shoppingLists as $shoppingList)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-950">{{ $shoppingList->parent_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $shoppingList->phone }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        <p>{{ $shoppingList->school_name ?? 'School pending' }}</p>
                                        <p class="text-xs">{{ $shoppingList->learner_name ?? 'Learner pending' }} {{ $shoppingList->class_level ? '- '.$shoppingList->class_level : '' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ ucfirst($shoppingList->delivery_preference) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded px-2 py-1 text-xs font-semibold {{ $shoppingList->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                            {{ \App\Models\ShoppingList::statuses()[$shoppingList->status] ?? ucfirst($shoppingList->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        {{ $shoppingList->estimated_total ? 'UGX '.number_format($shoppingList->estimated_total) : '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.shopping-lists.show', $shoppingList) }}" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">Review</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-500">No shopping lists have been uploaded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $shoppingLists->links() }}
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
