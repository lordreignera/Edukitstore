<x-admin-layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold text-[#071d4f]">Dashboard</h1>
                <p class="mt-1 text-sm text-slate-500">Welcome back, {{ str(auth()->user()->name)->before(' ') }}. Here is what needs attention today.</p>
            </div>
            <div class="inline-flex w-fit items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">
                <x-ui.icon name="clock" size="size-4" />
                {{ now()->format('D, d M Y') }}
            </div>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1500px] space-y-5">
            @if (session('status'))
                <div class="flex items-center gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    <x-ui.icon name="check" size="size-5" />
                    {{ session('status') }}
                </div>
            @endif

            @include('admin.partials.dashboard.stat-cards')

            @include('admin.partials.dashboard.insights')

            <div class="grid gap-5 xl:grid-cols-3">
                @include('admin.partials.dashboard.catalogue-table')
                @include('admin.partials.dashboard.review-queue')
            </div>

            @include('admin.partials.dashboard.recent-activity')
        </div>
    </div>
</x-admin-layout>
