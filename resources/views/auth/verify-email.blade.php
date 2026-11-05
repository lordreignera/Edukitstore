<x-guest-layout>
    <x-authentication-card title="Verify your email" subtitle="Confirm your email to track orders in your account. You can still shop and pay as a guest.">
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-4 text-sm text-gray-600">
            {{ __('We send a verification link when you register. If you still need one, use the button below while signed in. Check your inbox and spam folder.') }}
            <div class="mt-2 font-medium text-gray-800">{{ auth()->user()->email }}</div>
        </div>

        @if (session('status') && session('status') !== 'verification-link-sent')
            <div class="mb-4 rounded border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-medium text-orange-900">
                {{ session('status') }}
            </div>
        @endif

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 rounded border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-medium text-orange-900">
                {{ __('A new verification link was sent to the account email shown above. Check your inbox and spam folder.') }}
            </div>
        @endif

        <div class="mt-4 flex items-center justify-between">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <div>
                    <x-button type="submit">
                        {{ __('Send a new verification email') }}
                    </x-button>
                </div>
            </form>

            <div>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf

                    <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ms-2">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>
