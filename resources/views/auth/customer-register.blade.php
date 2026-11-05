<x-guest-layout>
    <x-authentication-card title="Create your customer account" subtitle="Verify your email to track orders, including guest orders placed with that same address.">
        <x-slot name="logo"><x-authentication-card-logo /></x-slot>

        <x-validation-errors class="mb-5" />

        <form method="POST" action="{{ route('customer.register.store') }}" class="space-y-5">
            @csrf

            <div>
                <x-label for="name" value="Your name" />
                <x-input id="name" class="mt-1.5 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            </div>

            <div>
                <x-label for="email" value="Email address" />
                <x-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autocomplete="email" placeholder="you@example.com" />
                <p class="mt-1 text-xs leading-5 text-slate-500">Use an email address you can access. We will send a verification link; no account is needed to place or pay for an order.</p>
            </div>

            <div>
                <x-label for="password" value="Password" />
                <div class="relative mt-1.5">
                    <x-input id="password" class="block w-full pr-12" type="password" name="password" required autocomplete="new-password" />
                    <button type="button" data-password-toggle data-password-target="password" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-500 hover:text-[#07215f]" aria-label="Show password" aria-pressed="false">
                        <x-ui.icon name="eye" size="size-5" />
                    </button>
                </div>
                <p class="mt-1 text-xs leading-5 text-slate-500">At least 8 characters, including uppercase and lowercase letters, a number, and a symbol.</p>
            </div>

            <div>
                <x-label for="password_confirmation" value="Confirm password" />
                <div class="relative mt-1.5">
                    <x-input id="password_confirmation" class="block w-full pr-12" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <button type="button" data-password-toggle data-password-target="password_confirmation" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-slate-500 hover:text-[#07215f]" aria-label="Show password confirmation" aria-pressed="false">
                        <x-ui.icon name="eye" size="size-5" />
                    </button>
                </div>
            </div>

            <x-button class="w-full">Create account</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">Already have an account? <a class="font-bold text-orange-700 hover:text-[#071d4f]" href="{{ route('login') }}">Sign in</a></p>
    </x-authentication-card>
</x-guest-layout>
