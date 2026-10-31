@php
    $guideLayout = $guide['layout'];
@endphp

@push('styles')
    <style>
        @media print {
            body { background: #fff !important; }
        }
    </style>
@endpush

@if ($guideLayout === 'admin')
    <x-admin-layout title="User Guide">
        <x-slot name="header">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-orange-700">EduKit help</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">User guide</h1>
                    <p class="mt-1 text-sm text-slate-500">Follow the workflow for your administrator workspace.</p>
                </div>
                <div class="flex flex-wrap gap-2 print:hidden">
                    <button type="button" onclick="window.print()" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-orange-300 px-4 text-sm font-extrabold text-orange-700 hover:bg-orange-50">Print / save as PDF</button>
                    <a href="{{ route('user-guide.download') }}" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-orange-700 px-4 text-sm font-extrabold text-white hover:bg-orange-800">Download guide</a>
                </div>
            </div>
        </x-slot>

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1500px]
                space-y-5">
                <x-dashboard-guide :eyebrow="$guide['eyebrow']" :title="$guide['title']" :intro="$guide['intro']" :steps="$guide['steps']" :notes="$guide['notes']" />
            </div>
        </div>
    </x-admin-layout>
@else
    <x-portal-layout title="User Guide" :portal-name="$guide['portalName']" :navigation="$guide['navigation']" theme="orange">
        <x-slot name="header">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-orange-700">EduKit help</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">User guide</h1>
                    <p class="mt-1 text-sm text-slate-500">Follow the workflow for your {{ strtolower($guide['portalName']) }}.</p>
                </div>
                <div class="flex flex-wrap gap-2 print:hidden">
                    <button type="button" onclick="window.print()" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-orange-300 px-4 text-sm font-extrabold text-orange-700 hover:bg-orange-50">Print / save as PDF</button>
                    <a href="{{ route('user-guide.download') }}" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-orange-700 px-4 text-sm font-extrabold text-white hover:bg-orange-800">Download guide</a>
                </div>
            </div>
        </x-slot>

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1500px]
                space-y-5">
                <x-dashboard-guide :eyebrow="$guide['eyebrow']" :title="$guide['title']" :intro="$guide['intro']" :steps="$guide['steps']" :notes="$guide['notes']" />
            </div>
        </div>
    </x-portal-layout>
@endif
