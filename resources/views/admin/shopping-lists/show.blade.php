<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Shopping List Intake</h2>
                <p class="mt-1 text-sm text-gray-500">Uploaded by {{ $shoppingList->parent_name }} on {{ $shoppingList->created_at->format('M d, Y') }}.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.invoices.show', $shoppingList) }}" class="rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Open invoice</a>
                <a href="{{ route('admin.shopping-lists.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back to lists</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
            <section class="rounded border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-gray-950">Submitted details</h3>
                @include('admin.invoices.partials.customer-summary', ['invoice' => $shoppingList])

                @if ($shoppingList->file_path)
                    <div class="mt-6 border-t border-gray-100 pt-5">
                        <p class="text-sm font-semibold text-gray-500">Uploaded file</p>
                        <a href="{{ route('admin.shopping-lists.download', $shoppingList) }}" class="mt-2 inline-flex rounded bg-[#07215f] px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                            Download {{ $shoppingList->original_filename }}
                        </a>
                    </div>
                @endif
            </section>

            <aside class="h-fit rounded border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase text-emerald-700">Next step</p>
                <h3 class="mt-2 text-xl font-black text-[#071d4f]">Prepare invoice</h3>
                <p class="mt-2 text-sm leading-6 text-emerald-900">Use the invoice workflow to price the request, assign an approved driver, release the invoice, receive payment, then wait for driver delivery confirmation.</p>
                <a href="{{ route('admin.invoices.show', $shoppingList) }}" class="mt-5 inline-flex rounded bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">Continue to invoice</a>
            </aside>
        </div>
    </div>
</x-admin-layout>
