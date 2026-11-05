<x-admin-layout>
    <x-slot name="title">Search</x-slot>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-extrabold text-[#071d4f]">Search the platform</h1>
            <p class="mt-1 text-sm text-slate-500">Results are limited to the modules your account is allowed to access.</p>
        </div>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-4">
            <form method="GET" action="{{ route('admin.search') }}" class="flex flex-col gap-2 rounded-lg border border-slate-200 bg-white p-3 shadow-sm sm:flex-row">
                <label for="global-search-query" class="sr-only">Search orders, products, schools, suppliers and drivers</label>
                <input id="global-search-query" name="q" value="{{ $term }}" minlength="2" maxlength="100" required autofocus placeholder="Search orders, products, schools, suppliers, drivers…" class="h-11 min-w-0 flex-1 rounded-md border-slate-300 text-sm focus:border-orange-600 focus:ring-orange-600">
                <button class="h-11 rounded-md bg-orange-600 px-5 text-sm font-bold text-white transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Search</button>
            </form>

            <p class="px-1 text-sm text-slate-500">Showing matches for <span class="font-bold text-slate-800">“{{ $term }}”</span></p>

            @php($matchCount = $resultGroups->sum(fn ($items) => $items->count()))
            @if ($matchCount === 0)
                <div class="rounded-lg border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
                    <h2 class="text-base font-bold text-slate-800">No matches found</h2>
                    <p class="mt-1 text-sm text-slate-500">Try another name, order reference, product code or phone number.</p>
                </div>
            @else
                @foreach ($resultGroups as $group => $results)
                    @if ($results->isNotEmpty())
                        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" aria-label="{{ $group }} search results">
                            <h2 class="border-b border-slate-100 px-4 py-3 text-sm font-extrabold text-[#071d4f]">{{ $group }} <span class="ml-1 text-xs font-semibold text-slate-400">{{ $results->count() }}</span></h2>
                            <ul class="divide-y divide-slate-100">
                                @foreach ($results as $result)
                                    <li><a href="{{ $result['url'] }}" class="flex min-h-14 items-center justify-between gap-4 px-4 py-3 transition hover:bg-orange-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-inset focus-visible:outline-orange-600">
                                        <span class="min-w-0"><span class="block truncate text-sm font-bold text-slate-800">{{ $result['title'] }}</span><span class="mt-0.5 block truncate text-xs text-slate-500">{{ $result['detail'] }}</span></span>
                                        <span class="shrink-0 text-sm font-bold text-orange-700" aria-hidden="true">→</span>
                                    </a></li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</x-admin-layout>
