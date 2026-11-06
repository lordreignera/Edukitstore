<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index', [
            'notifications' => auth()->user()->unreadNotifications()->latest()->paginate(25),
        ]);
    }

    public function open(string $notification): RedirectResponse
    {
        $notification = auth()->user()->notifications()->whereKey($notification)->firstOrFail();
        $notification->markAsRead();

        $url = (string) data_get($notification->data, 'url', route('admin.notifications.index'));
        $path = parse_url($url, PHP_URL_PATH);

        return redirect()->to(is_string($path) && str_starts_with($path, '/admin/')
            ? $path
            : route('admin.notifications.index'));
    }
}
