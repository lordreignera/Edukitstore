<x-app-layout>
    <x-slot name="header">
        <div><p class="text-sm font-bold text-emerald-700">EduKit account</p><h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Welcome, {{ auth()->user()->name }}</h1></div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-5 md:grid-cols-[1.3fr_0.7fr]">
                <section class="border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex items-start gap-4">
                        <div class="grid size-11 shrink-0 place-items-center rounded-md bg-emerald-100 text-emerald-700"><x-ui.icon name="check" size="size-6" /></div>
                        <div><h2 class="text-xl font-extrabold text-[#071d4f]">Your account is active</h2><p class="mt-2 leading-7 text-slate-600">You are signed in as <strong class="capitalize text-slate-800">{{ str(auth()->user()->getRoleNames()->first() ?? 'user')->replace('-', ' ') }}</strong>. Role-specific workspaces are being connected as the fulfilment modules are completed.</p></div>
                    </div>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('website.home') }}" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-[#07215f] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0b2f7c]">Visit the shop <x-ui.icon name="arrow-right" size="size-4" /></a>
                        <a href="{{ route('profile.show') }}" class="inline-flex min-h-11 items-center rounded-md border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Manage profile</a>
                    </div>
                </section>

                <aside class="border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase text-slate-500">Account details</p>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div><dt class="font-bold text-slate-800">Email</dt><dd class="mt-1 break-all text-slate-600">{{ auth()->user()->email }}</dd></div>
                        <div><dt class="font-bold text-slate-800">Role</dt><dd class="mt-1 capitalize text-slate-600">{{ str(auth()->user()->getRoleNames()->first() ?? 'User')->replace('-', ' ') }}</dd></div>
                        <div><dt class="font-bold text-slate-800">Access</dt><dd class="mt-1 font-bold text-emerald-700">Active</dd></div>
                    </dl>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
