@php
    $pushUser = auth()->user();
    $pushNotifications = app(\App\Services\PushNotificationService::class);
    $pushConfigured = $pushNotifications->isConfigured();
    $pushEnabled = $pushUser?->pushSubscriptions()->exists() ?? false;
@endphp

<div class="flex items-center gap-1.5">
    <button type="button"
        data-push-notifications
        data-vapid-public-key="{{ config('services.webpush.public_key') }}"
        data-subscribed="{{ $pushEnabled ? 'true' : 'false' }}"
        data-subscribe-url="{{ route('push-subscriptions.store') }}"
        data-unsubscribe-url="{{ route('push-subscriptions.destroy') }}"
        @disabled(! $pushConfigured)
        aria-label="{{ $pushConfigured ? ($pushEnabled ? 'Manage notifications on this device' : 'Enable notifications on this device') : 'Notification setup is required' }}"
        title="{{ $pushConfigured ? ($pushEnabled ? 'Manage notifications on this device' : 'Enable notifications on this device') : 'Phone alerts need VAPID keys configured' }}"
        class="inline-flex min-h-10 items-center gap-2 rounded-md border border-orange-200 px-2.5 text-xs font-bold text-[#071d4f] hover:border-orange-400 hover:bg-orange-50 disabled:cursor-not-allowed disabled:opacity-60 sm:px-3">
        <x-ui.icon name="bell" size="size-4" />
        <span data-push-notifications-label>{{ ! $pushConfigured ? 'Setup needed' : ($pushEnabled ? 'Notifications on' : 'Enable notifications') }}</span>
    </button>
    <span data-push-notifications-status class="max-w-36 text-[10px] leading-3 text-slate-600 sm:max-w-56 sm:text-xs sm:leading-4" aria-live="polite"></span>
</div>
