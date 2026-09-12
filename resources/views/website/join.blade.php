@extends('website.layout')

@section('title', 'Join EduKit')

@section('content')
<section class="bg-white">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <header class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-extrabold uppercase text-emerald-700">Join EduKit</p>
            <h1 class="mt-3 text-[32px] font-extrabold leading-tight text-[#07215f] sm:text-5xl">Work with Uganda's school supply network.</h1>
            <p class="mt-4 text-base leading-7 text-slate-600">Choose the application that matches your work. EduKit reviews every application before creating an account.</p>
        </header>

        <div class="mt-10 grid gap-5 md:grid-cols-2">
            <article class="border border-slate-200 bg-[#f8fbff] p-6 shadow-sm sm:p-8">
                <div class="grid size-12 place-items-center rounded-md bg-emerald-100 text-emerald-700"><x-ui.icon name="suppliers" size="size-6" /></div>
                <h2 class="mt-5 text-2xl font-extrabold text-[#07215f]">Become a supplier</h2>
                <p class="mt-3 leading-7 text-slate-600">Apply to supply books, stationery, uniforms, equipment and other school essentials.</p>
                <a href="{{ route('website.suppliers') }}" class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Supplier application <x-ui.icon name="arrow-right" size="size-4" /></a>
            </article>

            <article class="border border-slate-200 bg-[#f8fbff] p-6 shadow-sm sm:p-8">
                <div class="grid size-12 place-items-center rounded-md bg-blue-100 text-blue-700"><x-ui.icon name="drivers" size="size-6" /></div>
                <h2 class="mt-5 text-2xl font-extrabold text-[#07215f]">Become a delivery partner</h2>
                <p class="mt-3 leading-7 text-slate-600">Apply to collect and deliver verified school-supply orders across your operating area.</p>
                <a href="{{ route('website.drivers') }}" class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-md bg-[#07215f] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0b2f7c]">Delivery partner application <x-ui.icon name="arrow-right" size="size-4" /></a>
            </article>
        </div>

        <div class="mt-8 border-l-4 border-emerald-500 bg-emerald-50 px-5 py-4 text-sm leading-6 text-emerald-950">
            <strong>Schools, staff, finance teams and customers:</strong> your account is created by an EduKit administrator. Use the sign-in page after you receive your password setup link.
        </div>
    </div>
</section>
@endsection
