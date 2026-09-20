@extends('website.layout')

@section('title', 'Cart - EduKit')

@section('content')
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Cart</p>
            <h1 class="mt-2 text-3xl font-black text-[#07215f]">Your school supply cart</h1>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
        <div class="space-y-4">
            @if (session('status'))
                <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">Please check the highlighted cart details.</div>
            @endif

            @forelse ($products as $product)
                <article class="grid gap-4 rounded-md border border-[#dbe8f3] bg-white p-4 shadow-sm sm:grid-cols-[120px_1fr_220px]">
                    <div class="relative aspect-square overflow-hidden rounded-md bg-slate-50">
                        <x-ui.responsive-image
                            :src="$product->image_url"
                            :alt="$product->name"
                            :label="$product->category?->name ?? 'EduKit item'"
                            image-class="h-full w-full object-contain p-2"
                        />
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $product->category?->name ?? 'School supply' }}</p>
                        <h2 class="mt-1 text-lg font-black text-[#07215f]">{{ $product->name }}</h2>
                        <p class="mt-2 text-sm text-slate-600">UGX {{ number_format($product->cart_unit_price) }} each</p>
                        <p class="mt-1 text-xs font-bold {{ $product->cart_source['type'] === 'supplier' ? 'text-violet-700' : 'text-emerald-700' }}">{{ $product->cart_source['label'] }}</p>
                    </div>
                    <div class="space-y-3 sm:text-right">
                        <p class="font-black text-slate-950">UGX {{ number_format($product->cart_line_total) }}</p>
                        <form method="POST" action="{{ route('website.cart.update', $product) }}" class="flex items-center gap-2 sm:justify-end">
                            @csrf
                            @method('PATCH')
                            <label class="sr-only" for="cart-quantity-{{ $product->id }}">Quantity</label>
                            <input id="cart-quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ $product->cart_source['quantity'] }}" value="{{ $product->cart_quantity }}" class="h-10 w-20 rounded-md border-[#d7e4ef] text-center text-sm font-bold text-[#07215f] focus:border-emerald-600 focus:ring-emerald-600">
                            <button class="rounded-md border border-[#d7e4ef] px-3 py-2 text-xs font-bold text-[#07215f] hover:border-emerald-500">Update</button>
                        </form>
                        <form method="POST" action="{{ route('website.cart.destroy', $product) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm font-bold text-red-700 hover:text-red-900">Remove</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-md border border-dashed border-slate-300 bg-white p-8">
                    <p class="font-black text-[#07215f]">Your cart is empty.</p>
                    <p class="mt-2 text-sm text-slate-600">Start from the master catalogue and add school supplies.</p>
                    <a href="{{ route('website.products.index') }}" class="mt-5 inline-flex rounded-md bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Shop products</a>
                </div>
            @endforelse
        </div>

        @if ($products->isNotEmpty())
            <div class="mt-5 flex flex-col gap-3 rounded-md border border-[#dbe8f3] bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Cart subtotal</p>
                    <p class="mt-1 text-xl font-black text-[#07215f]">UGX {{ number_format($subtotal) }}</p>
                </div>
                <button type="button" data-open-order-summary class="inline-flex min-h-12 items-center justify-center rounded-md bg-[#07215f] px-5 py-3 text-sm font-black text-white hover:bg-emerald-700">
                    View order summary
                </button>
            </div>
        @endif
    </section>

    @if ($products->isNotEmpty())
        <dialog data-order-summary-dialog class="w-[min(94vw,720px)] rounded-md border border-[#dbe8f3] bg-white p-0 text-slate-950 shadow-2xl backdrop:bg-[#03133d]/60">
            <div class="max-h-[90vh] overflow-y-auto" data-school-delivery data-subtotal="{{ $subtotal }}" data-has-edukit-items="{{ $hasEdukitItems ? '1' : '0' }}" data-supplier-fees='@json($supplierFeeProfiles)'>
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-emerald-700">Order summary</p>
                        <h2 class="mt-1 text-2xl font-black text-[#07215f]">Review and pay</h2>
                    </div>
                    <button type="button" data-close-order-summary class="grid size-10 shrink-0 place-items-center rounded-md border border-slate-200 text-slate-500 hover:border-emerald-500 hover:text-[#07215f]" aria-label="Close order summary">
                        <x-ui.icon name="close" size="size-5" />
                    </button>
                </div>

                <div class="px-5 py-5 sm:px-6">
                    <div class="rounded-md bg-[#f6fbff] p-4">
                        <div class="flex justify-between text-sm">
                            <span class="font-semibold text-slate-600">Items subtotal</span>
                            <span class="font-black text-slate-950">UGX {{ number_format($subtotal) }}</span>
                        </div>
                        <div class="mt-3 flex justify-between text-sm">
                            <span class="font-semibold text-slate-600">Combined delivery fee</span>
                            <span class="font-black text-slate-950" data-delivery-fee>UGX 0</span>
                        </div>
                        <div class="mt-3 flex justify-between border-t border-slate-200 pt-3 text-base">
                            <span class="font-black text-[#07215f]">Total to pay</span>
                            <span class="font-black text-[#07215f]" data-grand-total>UGX {{ number_format($subtotal) }}</span>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-slate-500">EduKit warehouse and supplier-direct delivery charges are calculated for the selected school.</p>
                    </div>

                    <form method="POST" action="{{ route('website.cart.submit') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label for="parent_name" class="text-sm font-bold text-slate-700">Your name</label>
                            <input id="parent_name" name="parent_name" value="{{ old('parent_name') }}" required class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            @error('parent_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="phone" class="text-sm font-bold text-slate-700">Phone number</label>
                            <input id="phone" name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="email" class="text-sm font-bold text-slate-700">Email address</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <x-website.school-delivery-fields :schools="$schools" />
                        <div class="sm:col-span-2">
                            <label for="notes" class="text-sm font-bold text-slate-700">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('notes') }}</textarea>
                        </div>
                        <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs leading-5 text-slate-500">After submission, the invoice is ready for payment. Driver details appear after admin assignment.</p>
                            <button class="inline-flex min-h-12 justify-center rounded-md bg-[#07215f] px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Proceed to payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </dialog>
    @endif
@endsection

@push('scripts')
    @if ($products->isNotEmpty())
        <script>
            const orderSummaryDialog = document.querySelector('[data-order-summary-dialog]');

            document.querySelector('[data-open-order-summary]')?.addEventListener('click', () => {
                if (orderSummaryDialog?.showModal) {
                    orderSummaryDialog.showModal();
                }
            });

            document.querySelector('[data-close-order-summary]')?.addEventListener('click', () => {
                orderSummaryDialog?.close();
            });

            orderSummaryDialog?.addEventListener('click', (event) => {
                if (event.target === orderSummaryDialog) {
                    orderSummaryDialog.close();
                }
            });

            @if ($errors->any())
                orderSummaryDialog?.showModal();
            @endif
        </script>

        @include('website.partials.forms.school-delivery-script')
    @endif
@endpush
