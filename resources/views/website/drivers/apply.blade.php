@extends('website.layout')

@section('title', 'Become a Delivery Partner - EduKit')

@section('content')
<section class="bg-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8 lg:py-14">
        <div>
            <p class="text-sm font-black uppercase text-emerald-700">Delivery partners</p>
            <h1 class="mt-3 text-4xl font-black leading-tight text-[#07215f] sm:text-5xl">Deliver school essentials with care.</h1>
            <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">Tell us about your operating area and vehicle. EduKit verifies every application before activating a delivery-partner account.</p>
            <div class="mt-8 space-y-3 text-sm text-slate-700">
                <div class="border border-slate-200 bg-[#f8fbff] p-4"><p class="font-black text-[#07215f]">Application review</p><p class="mt-1">Your identity, contact details and vehicle information are reviewed by an administrator.</p></div>
                <div class="border border-slate-200 bg-[#f8fbff] p-4"><p class="font-black text-[#07215f]">Account activation</p><p class="mt-1">Create your password now. Once approved, sign in with the same email and password.</p></div>
            </div>
        </div>

        <form method="POST" action="{{ route('website.drivers.store') }}" enctype="multipart/form-data" class="border border-slate-200 bg-[#f8fbff] p-5 shadow-sm sm:p-7">
            @csrf

            @if (session('status'))
                <div class="mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="text-sm font-bold text-slate-700" for="name">Full name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="phone">Phone number</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="email">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="driver_password">Create password</label>
                    <div class="relative mt-1">
                        <input id="driver_password" name="password" type="password" required autocomplete="new-password" class="w-full rounded border-slate-300 pr-12 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <button type="button" data-password-toggle data-password-target="driver_password" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-500 hover:text-[#07215f]" aria-label="Show password" aria-pressed="false"><x-ui.icon name="eye" size="size-5" /></button>
                    </div>
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="driver_password_confirmation">Confirm password</label>
                    <div class="relative mt-1">
                        <input id="driver_password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded border-slate-300 pr-12 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <button type="button" data-password-toggle data-password-target="driver_password_confirmation" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-500 hover:text-[#07215f]" aria-label="Show password confirmation" aria-pressed="false"><x-ui.icon name="eye" size="size-5" /></button>
                    </div>
                </div>
                <x-district-select :districts="$districts" label="Operating district" />
                <div>
                    <label class="text-sm font-bold text-slate-700" for="vehicle_type">Vehicle type</label>
                    <input id="vehicle_type" name="vehicle_type" value="{{ old('vehicle_type') }}" placeholder="Motorcycle, van or car" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    @error('vehicle_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="vehicle_registration">Vehicle registration</label>
                    <input id="vehicle_registration" name="vehicle_registration" value="{{ old('vehicle_registration') }}" required class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    @error('vehicle_registration') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-700" for="payment_phone">Payout mobile money phone</label>
                    <input id="payment_phone" name="payment_phone" value="{{ old('payment_phone') }}" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    @error('payment_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-bold text-slate-700" for="verification_document">Identification or driving document</label>
                    <input id="verification_document" name="verification_document" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="mt-1 block w-full rounded border border-slate-300 bg-white text-sm file:mr-4 file:border-0 file:bg-[#07215f] file:px-4 file:py-2.5 file:font-bold file:text-white">
                    <p class="mt-1 text-xs text-slate-500">PDF, image or Word document. Maximum 8 MB.</p>
                    @error('verification_document') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-bold text-slate-700" for="notes">Additional details</label>
                    <textarea id="notes" name="notes" rows="4" class="mt-1 w-full rounded border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('notes') }}</textarea>
                    @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <button class="mt-7 min-h-11 rounded-md bg-emerald-600 px-6 py-2.5 text-sm font-black text-white hover:bg-emerald-700">Submit application</button>
        </form>
    </div>
</section>
@endsection
