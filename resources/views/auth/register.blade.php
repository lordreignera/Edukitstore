<x-guest-layout>
    <x-authentication-card title="Create your account" subtitle="Join EduKit to shop and manage school supplies with ease.">
        <x-slot name="logo"><x-authentication-card-logo /></x-slot>

        <x-validation-errors class="mb-5" />

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <x-label for="name" value="Full name" />
                <x-input id="name" class="mt-1.5 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Your full name" />
            </div>

            <div>
                <x-label for="email" value="Email address" />
                <x-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="you@example.com" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-label for="password" value="Password" />
                    <x-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="new-password" />
                </div>
                <div>
                    <x-label for="password_confirmation" value="Confirm password" />
                    <x-input id="password_confirmation" class="mt-1.5 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <label for="terms" class="flex items-start gap-2 text-sm leading-5 text-slate-600">
                    <x-checkbox name="terms" id="terms" required class="mt-0.5 text-emerald-700 focus:ring-emerald-600" />
                    <span>{!! __('I agree to the :terms_of_service and :privacy_policy', [
                        'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="font-bold text-emerald-700">'.__('Terms of Service').'</a>',
                        'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="font-bold text-emerald-700">'.__('Privacy Policy').'</a>',
                    ]) !!}</span>
                </label>
            @endif

            <x-button class="mt-2 w-full">Create account</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">Already registered? <a href="{{ route('login') }}" class="font-bold text-emerald-700 hover:text-[#071d4f]">Sign in</a></p>
    </x-authentication-card>
</x-guest-layout>
