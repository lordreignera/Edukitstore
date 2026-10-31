@if (auth()->user()?->must_change_password)
    <div role="status" class="border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p><strong>Security reminder:</strong> You are still using your temporary password. Please choose a private password.</p>
            <a href="{{ route('password.change') }}" class="font-bold underline underline-offset-2 hover:text-amber-700">Change password</a>
        </div>
    </div>
@endif
