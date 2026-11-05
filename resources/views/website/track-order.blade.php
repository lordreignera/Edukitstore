@extends('website.layout')

@section('title', 'Track an Order - EduKit')

@section('content')
    <section class="bg-white">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="rounded-md border border-orange-200 bg-orange-50 p-6 sm:p-9">
                <p class="text-xs font-extrabold uppercase tracking-wide text-orange-800">Order tracking</p>
                <h1 class="mt-3 text-3xl font-extrabold leading-tight text-[#07215f]">All your orders, in one place.</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-700">You can place and pay for an order as a guest. To track it later, sign in or create an account using the email on your order, then verify that email address.</p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ auth()->check() ? route('customer.dashboard') : route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-orange-600 px-5 py-3 text-sm font-extrabold text-white hover:bg-orange-700">{{ auth()->check() ? 'Open my orders' : 'Sign in to track orders' }}</a>
                    @guest
                        <a href="{{ route('customer.register') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-orange-300 bg-white px-5 py-3 text-sm font-bold text-[#07215f] hover:border-orange-500">Create account</a>
                    @endguest
                    <a href="{{ route('website.products.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-[#07215f] hover:border-orange-400">Browse products</a>
                    <a href="{{ route('website.help') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-[#07215f] hover:border-orange-400">Get help</a>
                </div>
            <p class="mt-5 border-t border-orange-200 pt-4 text-xs leading-5 text-slate-600">Guest orders are added to your account only after you verify that the account email matches the email used on the order.</p>
            </div>
        </div>
    </section>
@endsection
