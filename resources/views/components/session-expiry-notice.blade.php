<section data-session-idle-warning hidden role="alertdialog" aria-modal="false" aria-labelledby="session-idle-title" aria-describedby="session-idle-message" style="display: none" class="fixed inset-x-4 top-4 z-[100] mx-auto flex max-w-3xl flex-col gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950 shadow-xl sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p id="session-idle-title" class="font-bold">Still there?</p>
        <p id="session-idle-message" class="mt-1 text-sm">For your security, EduKit will sign you out in <strong data-session-countdown>60 seconds</strong> because of inactivity.</p>
    </div>
    <div class="flex shrink-0 flex-wrap gap-2">
        <button type="button" data-session-stay-active class="rounded-lg bg-[#071d4f] px-4 py-2 text-sm font-bold text-white hover:bg-[#102a63] focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Stay signed in</button>
        <button type="button" data-session-sign-out class="rounded-lg border border-amber-400 bg-white px-4 py-2 text-sm font-bold text-amber-950 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Sign out now</button>
    </div>
</section>

<div data-session-expired-notice hidden role="alert" aria-live="assertive" style="display: none" class="fixed inset-x-4 top-4 z-[100] mx-auto flex max-w-3xl flex-col gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950 shadow-xl sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="font-bold">You have been signed out.</p>
        <p data-session-expired-message class="mt-1 text-sm">Your secure session expired. Sign in again to continue.</p>
    </div>
    <a data-session-expired-login href="{{ route('login') }}" class="shrink-0 rounded-lg bg-[#071d4f] px-4 py-2 text-center text-sm font-bold text-white hover:bg-[#102a63] focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Sign in again</a>
</div>
