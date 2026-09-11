@extends('website.layout')

@section('title', $page['title'] . ' - EduKit')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <p class="text-sm font-black uppercase tracking-wide text-emerald-700">{{ $page['title'] }}</p>
        <h1 class="mt-3 text-4xl font-black text-[#07215f]">{{ $page['heading'] }}</h1>
        <p class="mx-auto mt-4 max-w-2xl leading-7 text-slate-600">{{ $page['copy'] }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('website.products.index') }}" class="rounded bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Shop products</a>
            <a href="{{ route('website.home') }}" class="rounded border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-[#07215f] hover:border-emerald-500">Back home</a>
        </div>
    </section>
@endsection
