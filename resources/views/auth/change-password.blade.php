<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-bold text-orange-700">EduKit account setup</p>
            <h1 class="mt-1 text-2xl font-extrabold text-[#071d4f]">Secure your {{ str(auth()->user()->getRoleNames()->first() ?? 'EduKit')->replace('-', ' ') }} account</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8" x-data x-on:saved.window="window.location.assign('{{ route('dashboard') }}')">
        <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            Your account uses a temporary password. For your security, change it here or later from Profile settings. You can continue working in your role workspace now.
        </div>

        <a href="{{ route('dashboard') }}" class="mb-6 inline-flex rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-[#071d4f] hover:bg-slate-50">Go to my workspace</a>

        @livewire('profile.update-password-form')
    </div>
</x-app-layout>
