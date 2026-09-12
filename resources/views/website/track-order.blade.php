@extends('website.layout')

@section('title', 'Track Invoice - EduKit')

@section('content')
    <section class="bg-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
            <div>
                <p class="text-[11px] font-extrabold uppercase text-emerald-700">Track invoice</p>
                <h1 class="mt-3 text-[36px] font-extrabold leading-tight text-[#07215f]">Check if your EduKit invoice is ready.</h1>
                <p class="mt-4 max-w-xl text-sm leading-7 text-slate-600">Use the invoice reference sent after submitting your cart or shopping list, plus the phone number or email used on the request.</p>
            </div>

            <form method="POST" action="{{ route('website.track-order.lookup') }}" class="rounded-md border border-[#dbe8f3] bg-[#f8fbff] p-5 shadow-sm sm:p-7">
                @csrf
                @if ($errors->any())
                    <div class="mb-5 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
                @endif

                <div>
                    <label for="reference" class="text-sm font-bold text-slate-700">Invoice reference</label>
                    <input id="reference" name="reference" value="{{ old('reference') }}" placeholder="EDK-260912-ABCDE" required class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm uppercase focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="mt-4">
                    <label for="contact" class="text-sm font-bold text-slate-700">Phone number or email</label>
                    <input id="contact" name="contact" value="{{ old('contact') }}" placeholder="+256700123456 or name@example.com" required class="mt-1 w-full rounded-md border-[#d7e4ef] text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <button class="mt-6 flex w-full justify-center rounded-md bg-[#07215f] px-5 py-3 text-sm font-extrabold text-white hover:bg-emerald-700">Open invoice</button>
            </form>
        </div>
    </section>
@endsection
