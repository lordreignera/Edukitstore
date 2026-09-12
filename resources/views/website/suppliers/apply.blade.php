@extends('website.layout')

@section('title', 'Become a Supplier - EduKit')

@section('content')
    <section class="bg-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-14">
            <div>
                <p class="text-sm font-black uppercase tracking-wide text-emerald-700">For suppliers</p>
                <h1 class="mt-3 text-4xl font-black leading-tight text-[#07215f] sm:text-5xl">Supply school essentials across Uganda.</h1>
                <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">
                    Apply to join EduKit&apos;s verified supplier network. Approved suppliers can be matched to school-list orders based on location, product category, capacity and reliability.
                </p>

                <div class="mt-8 space-y-3 text-sm text-slate-700">
                    <div class="rounded border border-slate-200 bg-[#f8fbff] p-4">
                        <p class="font-black text-[#07215f]">Verified marketplace</p>
                        <p class="mt-1">Admins approve suppliers before fulfilment work starts.</p>
                    </div>
                    <div class="rounded border border-slate-200 bg-[#f8fbff] p-4">
                        <p class="font-black text-[#07215f]">Category matching</p>
                        <p class="mt-1">Tell us whether you supply books, stationery, bags, toiletries, uniforms or other school items.</p>
                    </div>
                    <div class="rounded border border-slate-200 bg-[#f8fbff] p-4">
                        <p class="font-black text-[#07215f]">Fulfilment ready</p>
                        <p class="mt-1">EduKit will later connect approved suppliers to order preparation and dispatch workflows.</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('website.suppliers.store') }}" enctype="multipart/form-data" class="rounded border border-slate-200 bg-[#f8fbff] p-5 shadow-sm sm:p-7">
                @csrf
                @php
                    $selectedCategoryIds = collect(old('product_category_ids', []))->map(fn ($id) => (string) $id)->all();
                    $hasOtherCategories = filled(old('other_product_categories'));
                    $showOtherCategories = $hasOtherCategories || $productCategories->isEmpty();
                @endphp

                @if (session('status'))
                    <div class="mb-6 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-sm font-bold text-slate-700" for="business_name">Business name</label>
                        <input id="business_name" name="business_name" value="{{ old('business_name') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('business_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="contact_person">Contact person</label>
                        <input id="contact_person" name="contact_person" value="{{ old('contact_person') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('contact_person') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="phone">Phone number</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="district">District</label>
                        <input id="district" name="district" value="{{ old('district') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('district') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="password">Create password</label>
                        <div class="relative mt-1">
                            <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded border-slate-300 pr-12 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <button type="button" data-password-toggle data-password-target="password" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-500 hover:text-[#07215f]" aria-label="Show password" aria-pressed="false">
                                <x-ui.icon name="eye" size="size-5" />
                            </button>
                        </div>
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="password_confirmation">Confirm password</label>
                        <div class="relative mt-1">
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded border-slate-300 pr-12 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <button type="button" data-password-toggle data-password-target="password_confirmation" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-500 hover:text-[#07215f]" aria-label="Show password confirmation" aria-pressed="false">
                                <x-ui.icon name="eye" size="size-5" />
                            </button>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-sm font-bold text-slate-700" for="address">Shop or warehouse address</label>
                        <input id="address" name="address" value="{{ old('address') }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <fieldset>
                            <legend class="text-sm font-bold text-slate-700">Product categories supplied</legend>
                            <p class="mt-1 text-xs font-semibold text-slate-500">Pick from EduKit&apos;s product catalogue. Use Other for items not listed yet.</p>

                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @forelse ($productCategories as $category)
                                    <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-[#07215f] transition hover:border-emerald-500">
                                        <input type="checkbox" name="product_category_ids[]" value="{{ $category->id }}" @checked(in_array((string) $category->id, $selectedCategoryIds, true)) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                                        <span>{{ $category->name }}</span>
                                    </label>
                                @empty
                                    <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">
                                        No catalogue categories are active yet. Add your supplied items under Other.
                                    </div>
                                @endforelse

                                <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-[#07215f] transition hover:border-emerald-500">
                                    <input id="other_category_toggle" type="checkbox" @checked($showOtherCategories) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                                    <span>Other</span>
                                </label>
                            </div>

                            <div id="other_category_wrap" class="{{ $showOtherCategories ? '' : 'hidden' }} mt-3">
                                <label class="sr-only" for="other_product_categories">Other product categories</label>
                                <textarea id="other_product_categories" name="other_product_categories" rows="2" class="w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Example: laboratory items, sportswear, art materials...">{{ old('other_product_categories') }}</textarea>
                            </div>
                        </fieldset>
                        @error('product_category_ids') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('other_product_categories') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="supply_capacity">Supply capacity</label>
                        <input id="supply_capacity" name="supply_capacity" value="{{ old('supply_capacity') }}" placeholder="Example: 200 orders per week" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('supply_capacity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="verification_document">Verification document</label>
                        <input id="verification_document" name="verification_document" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="mt-1 block w-full rounded border border-slate-300 bg-white p-2 text-sm text-slate-700 file:mr-3 file:rounded file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-emerald-700">
                        @error('verification_document') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-sm font-bold text-slate-700" for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="4" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Mention brands, districts covered, delivery readiness or special school supply strengths.">{{ old('notes') }}</textarea>
                        @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button class="mt-7 w-full rounded bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-emerald-700 sm:w-auto">Submit supplier application</button>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordTarget);
                const showing = input.type === 'text';

                input.type = showing ? 'password' : 'text';
                button.setAttribute('aria-pressed', showing ? 'false' : 'true');
                button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
                button.innerHTML = showing
                    ? '<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>'
                    : '<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.8 5.2A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-2 2.8"></path><path d="M6.6 6.6C3.6 8.5 2 12 2 12s3.5 7 10 7a9.8 9.8 0 0 0 4.2-.9"></path></svg>';
            });
        });

        const otherToggle = document.getElementById('other_category_toggle');
        const otherWrap = document.getElementById('other_category_wrap');
        const otherInput = document.getElementById('other_product_categories');

        otherToggle?.addEventListener('change', () => {
            otherWrap.classList.toggle('hidden', ! otherToggle.checked);

            if (! otherToggle.checked) {
                otherInput.value = '';
            } else {
                otherInput.focus();
            }
        });
    </script>
@endpush
