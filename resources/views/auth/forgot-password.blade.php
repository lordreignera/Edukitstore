<x-guest-layout>
    <x-authentication-card title="Reset your password" subtitle="Enter your account email and we will send you a secure reset link.">
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="block">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button class="w-full">
                    {{ __('Email Password Reset Link') }}
                </x-button>
            </div>
            <p class="mt-5 text-center text-sm text-slate-600"><a href="{{ route('login') }}" class="font-bold text-emerald-700">Back to sign in</a></p>
        </form>
    </x-authentication-card>
</x-guest-layout>
