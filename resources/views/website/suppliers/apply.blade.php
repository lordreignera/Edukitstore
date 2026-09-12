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
                        <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-bold text-slate-700" for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-sm font-bold text-slate-700" for="address">Shop or warehouse address</label>
                        <input id="address" name="address" value="{{ old('address') }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-sm font-bold text-slate-700" for="product_categories">Product categories supplied</label>
                        <textarea id="product_categories" name="product_categories" rows="3" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Books, stationery, uniforms, bags, toiletries, bedding...">{{ old('product_categories') }}</textarea>
                        @error('product_categories') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
