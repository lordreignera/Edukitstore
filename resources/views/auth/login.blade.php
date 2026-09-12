<x-guest-layout>
    <x-authentication-card title="Welcome back" subtitle="Sign in to continue to your EduKit workspace.">
        <x-slot name="logo"><x-authentication-card-logo /></x-slot>

        <x-validation-errors class="mb-5" />

        @session('status')
            <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ $value }}</div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <x-label for="email" value="Email address" />
                <div class="relative mt-1.5">
                    <x-ui.icon name="mail" size="size-[18px]" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <x-input id="email" class="block w-full pl-10" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
                </div>
            </div>

            <div x-data="{ showPassword: false }">
                <div class="flex items-center justify-between gap-4">
                    <x-label for="password" value="Password" />
                    @if (Route::has('password.request'))
                        <a class="text-xs font-bold text-emerald-700 hover:text-[#071d4f]" href="{{ route('password.request') }}">Forgot password?</a>
                    @endif
                </div>
                <div class="relative mt-1.5">
                    <x-ui.icon name="lock" size="size-[18px]" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input id="password" class="h-11 w-full rounded-md border-slate-300 bg-white pl-10 pr-11 text-sm text-slate-900 focus:border-emerald-600 focus:ring-emerald-600" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password">
                    <button type="button" class="absolute right-1.5 top-1/2 grid size-8 -translate-y-1/2 place-items-center text-slate-400 hover:text-slate-700" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                        <span x-show="!showPassword"><x-ui.icon name="eye" size="size-[18px]" /></span>
                        <span x-cloak x-show="showPassword"><x-ui.icon name="eye-off" size="size-[18px]" /></span>
                    </button>
                </div>
            </div>

            <label for="remember_me" class="flex w-fit items-center gap-2 text-sm font-medium text-slate-600">
                <x-checkbox id="remember_me" name="remember" class="text-emerald-700 focus:ring-emerald-600" />
                <span>Keep me signed in</span>
            </label>

            <x-button class="w-full">Sign in</x-button>
        </form>

        @if (Route::has('register'))
            <p class="mt-6 text-center text-sm text-slate-600">New to EduKit? <a href="{{ route('register') }}" class="font-bold text-emerald-700 hover:text-[#071d4f]">Create an account</a></p>
        @endif
    </x-authentication-card>
</x-guest-layout>
