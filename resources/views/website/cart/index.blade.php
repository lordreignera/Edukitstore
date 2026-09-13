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
                        <p class="mt-2 text-sm text-slate-600">UGX {{ number_format($product->price) }} each</p>
                    </div>
                    <div class="space-y-3 sm:text-right">
                        <p class="font-black text-slate-950">UGX {{ number_format($product->cart_line_total) }}</p>
                        <form method="POST" action="{{ route('website.cart.update', $product) }}" class="flex items-center gap-2 sm:justify-end">
                            @csrf
                            @method('PATCH')
                            <label class="sr-only" for="cart-quantity-{{ $product->id }}">Quantity</label>
                            <input id="cart-quantity-{{ $product->id }}" name="quantity" type="number" min="1" max="{{ $product->stock_quantity }}" value="{{ $product->cart_quantity }}" class="h-10 w-20 rounded-md border-[#d7e4ef] text-center text-sm font-bold text-[#07215f] focus:border-emerald-600 focus:ring-emerald-600">
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
            <div class="max-h-[90vh] overflow-y-auto">
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
                            <span class="font-semibold text-slate-600">School delivery fee</span>
                            <span class="font-black text-slate-950" data-delivery-fee>UGX 0</span>
                        </div>
                        <div class="mt-3 flex justify-between border-t border-slate-200 pt-3 text-base">
                            <span class="font-black text-[#07215f]">Total to pay</span>
                            <span class="font-black text-[#07215f]" data-grand-total>UGX {{ number_format($subtotal) }}</span>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-slate-500">The delivery fee is pulled from the selected school record set by EduKit admin.</p>
                    </div>

                    <form method="POST" action="{{ route('website.cart.submit') }}" class="mt-5 grid gap-4 sm:grid-cols-2" data-checkout-form data-subtotal="{{ $subtotal }}">
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
                        <div>
                            <label for="delivery_preference" class="text-sm font-bold text-slate-700">Delivery option</label>
                            <select id="delivery_preference" name="delivery_preference" required data-delivery-preference class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <option value="school" @selected(old('delivery_preference', 'school') === 'school')>Deliver to school</option>
                                <option value="pickup" @selected(old('delivery_preference') === 'pickup')>Pickup from warehouse</option>
                            </select>
                        </div>
                        <div data-school-fields>
                            <label for="school_id" class="text-sm font-bold text-slate-700">School</label>
                            <select id="school_id" name="school_id" data-school-select class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <option value="" data-fee="0">Select school</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}" data-fee="{{ $school->delivery_fee }}" data-location="{{ $school->location }}" data-district="{{ $school->district?->name }}" @selected((int) old('school_id') === $school->id)>
                                        {{ $school->name }} - {{ $school->district?->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('school_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div data-school-fields>
                            <label for="learner_name" class="text-sm font-bold text-slate-700">Student name</label>
                            <input id="learner_name" name="learner_name" value="{{ old('learner_name') }}" class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            @error('learner_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div data-school-fields>
                            <label for="class_level" class="text-sm font-bold text-slate-700">Class / stream</label>
                            <input id="class_level" name="class_level" value="{{ old('class_level') }}" class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            @error('class_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <div class="rounded-md border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-950" data-school-fee-card>
                                <p class="font-black">Selected school fee: <span data-school-fee-text>UGX 0</span></p>
                                <p class="mt-1 text-xs leading-5 text-emerald-800" data-school-location-text>Select a school to show the destination fee.</p>
                            </div>
                        </div>
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

            const checkoutForm = document.querySelector('[data-checkout-form]');
            const subtotal = Number(checkoutForm?.dataset.subtotal || 0);
            const deliveryPreference = document.querySelector('[data-delivery-preference]');
            const schoolSelect = document.querySelector('[data-school-select]');
            const schoolFields = document.querySelectorAll('[data-school-fields]');
            const deliveryFeeText = document.querySelector('[data-delivery-fee]');
            const grandTotalText = document.querySelector('[data-grand-total]');
            const schoolFeeText = document.querySelector('[data-school-fee-text]');
            const schoolLocationText = document.querySelector('[data-school-location-text]');
            const learnerInput = document.getElementById('learner_name');
            const classInput = document.getElementById('class_level');
            const formatUgx = (amount) => `UGX ${Number(amount || 0).toLocaleString('en-US')}`;

            const syncCheckoutTotals = () => {
                const isSchoolDelivery = deliveryPreference?.value === 'school';
                const selectedSchool = schoolSelect?.selectedOptions?.[0];
                const fee = isSchoolDelivery ? Number(selectedSchool?.dataset.fee || 0) : 0;
                const location = selectedSchool?.dataset.location || selectedSchool?.dataset.district || '';

                schoolFields.forEach((field) => {
                    field.classList.toggle('hidden', ! isSchoolDelivery);
                });

                if (schoolSelect) {
                    schoolSelect.required = isSchoolDelivery;
                }

                if (learnerInput && classInput) {
                    learnerInput.required = isSchoolDelivery;
                    classInput.required = isSchoolDelivery;
                }

                deliveryFeeText.textContent = formatUgx(fee);
                grandTotalText.textContent = formatUgx(subtotal + fee);
                schoolFeeText.textContent = formatUgx(fee);
                schoolLocationText.textContent = isSchoolDelivery
                    ? (selectedSchool?.value ? `Delivery to ${location || selectedSchool.textContent.trim()}.` : 'Select a school to show the destination fee.')
                    : 'Pickup from the EduKit warehouse has no delivery fee.';
            };

            deliveryPreference?.addEventListener('change', syncCheckoutTotals);
            schoolSelect?.addEventListener('change', syncCheckoutTotals);
            syncCheckoutTotals();

            @if ($errors->any())
                orderSummaryDialog?.showModal();
            @endif
        </script>
    @endif
@endpush
