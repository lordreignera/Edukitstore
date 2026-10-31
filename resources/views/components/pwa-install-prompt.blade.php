<div data-pwa-install hidden class="fixed inset-x-4 bottom-4 z-[100] mx-auto max-w-md rounded-xl border border-orange-200 bg-white p-4 shadow-2xl shadow-orange-950/20">
    <div class="flex items-start gap-3">
        <img src="{{ asset('images/website/EDUKIT_STORE_logo2.png') }}" alt="EduKit" class="size-12 shrink-0 rounded-xl object-cover">
        <div class="min-w-0 flex-1">
            <p class="font-extrabold text-[#071d4f]">Install EduKit</p>
            <p data-pwa-install-message class="mt-1 text-sm leading-5 text-slate-600">Add EduKit to your device for faster access.</p>
        </div>
        <button type="button" data-pwa-install-dismiss class="grid size-8 shrink-0 place-items-center rounded-full text-xl leading-none text-slate-500 hover:bg-orange-50 hover:text-orange-700" aria-label="Dismiss install message">&times;</button>
    </div>
    <div class="mt-3 flex items-center justify-end gap-2">
        <button type="button" data-pwa-install-dismiss class="rounded-md px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100">Not now</button>
        <button type="button" data-pwa-install-action class="rounded-md bg-orange-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-orange-700">Install app</button>
    </div>
</div>
