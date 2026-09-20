<section class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
    <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-center">
        <div class="flex items-start gap-4">
            <span class="mt-0.5 grid size-11 shrink-0 place-items-center rounded-full {{ $driver->is_available ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}"><x-ui.icon name="drivers" size="size-5" /></span>
            <div><div class="flex flex-wrap items-center gap-2"><h2 class="font-extrabold text-[#071d4f]">{{ $driver->is_available ? 'Available for new trips' : 'Unavailable for new trips' }}</h2><span class="size-2.5 rounded-full {{ $driver->is_available ? 'bg-emerald-500' : 'bg-slate-400' }}"></span></div><p class="mt-1 text-sm text-slate-500">{{ $driver->is_available ? 'Dispatch can assign new deliveries to you.' : ($driver->availability_note ?: 'Dispatch will not assign new trips until you become available.') }}</p>@if ($driver->availability_updated_at)<p class="mt-1 text-xs text-slate-400">Updated {{ $driver->availability_updated_at->diffForHumans() }}</p>@endif</div>
        </div>
        @if ($driver->is_available)
            <form method="POST" action="{{ route('driver.availability') }}" class="flex flex-col gap-2 sm:flex-row">@csrf @method('PATCH')<input type="hidden" name="is_available" value="0"><input name="availability_note" maxlength="160" placeholder="Reason, e.g. vehicle service" class="h-10 rounded-md border-slate-300 text-sm"><button class="h-10 whitespace-nowrap rounded-md border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">Go unavailable</button></form>
        @else
            <form method="POST" action="{{ route('driver.availability') }}">@csrf @method('PATCH')<input type="hidden" name="is_available" value="1"><button class="h-10 rounded-md bg-emerald-700 px-5 text-sm font-bold text-white hover:bg-emerald-800">I am available</button></form>
        @endif
    </div>
</section>
