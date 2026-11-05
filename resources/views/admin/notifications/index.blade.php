<x-admin-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-extrabold text-[#071d4f]">Notifications</h1>
            <p class="mt-1 text-sm text-slate-500">Order alerts for your EduKit administrator account.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-3 px-4 py-6 sm:px-6 lg:px-8">
        @unless (app(\App\Services\PushNotificationService::class)->isConfigured())
            <div class="rounded-md border border-orange-200 bg-orange-50 p-4 text-sm leading-6 text-orange-950">
                <p class="font-bold">Phone push alerts need setup</p>
                <p class="mt-1">Generate a VAPID key pair with <code>php artisan edukit:generate-vapid-keys</code>, add both values and your public site URL to the server environment, then clear the config cache. Open this page on each admin device and tap <strong>Enable phone alerts</strong>.</p>
            </div>
        @endunless

        @forelse ($notifications as $notification)
            @php($data = $notification->data)
            <form method="POST" action="{{ route('admin.notifications.open', $notification->id) }}" class="flex items-start justify-between gap-4 rounded-md border {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-orange-300 bg-orange-50/60' }} p-4 shadow-sm">
                @csrf
                <div>
                    <p class="font-bold text-[#071d4f]">{{ $data['title'] ?? 'EduKit notification' }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $data['body'] ?? '' }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                <button class="shrink-0 rounded-md bg-[#07215f] px-3 py-2 text-xs font-bold text-white hover:bg-orange-700">{{ $notification->read_at ? 'Open' : 'Review' }}</button>
            </form>
        @empty
            <div class="rounded-md border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">No order notifications yet.</div>
        @endforelse

        {{ $notifications->links() }}
    </div>
</x-admin-layout>
