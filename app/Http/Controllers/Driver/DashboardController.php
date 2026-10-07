<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $driver = auth()->user()->driver;

        abort_unless($driver?->is_approved, 403);

        $deliveries = ShoppingList::query()
            ->where('assigned_driver_id', $driver->id)
            ->whereNotIn('status', [ShoppingList::STATUS_REJECTED, ShoppingList::STATUS_CANCELLED]);
        $stats = [
            'assigned' => (clone $deliveries)->count(),
            'pending' => (clone $deliveries)->whereNull('customer_received_at')->whereNull('delivery_confirmed_at')->count(),
            'ready' => (clone $deliveries)->where('payment_status', ShoppingList::PAYMENT_PAID)->where('status', ShoppingList::STATUS_QUOTED)->whereNull('payment_exception')->whereNull('driver_started_at')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at')->count(),
            'in_transit' => (clone $deliveries)->whereNotNull('driver_started_at')->whereNull('driver_reached_at')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at')->count(),
            'delivered' => (clone $deliveries)->where(function ($query) {
                $query->whereNotNull('customer_received_at')->orWhereNotNull('delivery_confirmed_at');
            })->count(),
        ];

        $activeDeliveries = (clone $deliveries)
            ->with('school.district')
            ->whereNull('customer_received_at')
            ->whereNull('delivery_confirmed_at')
            ->orderByRaw("CASE WHEN payment_status = ? THEN 0 ELSE 1 END", [ShoppingList::PAYMENT_PAID])
            ->latest('paid_at')
            ->take(6)
            ->get();

        $recentCompleted = (clone $deliveries)
            ->with('school')
            ->where(function ($query) {
                $query->whereNotNull('customer_received_at')->orWhereNotNull('delivery_confirmed_at');
            })
            ->latest('delivery_confirmed_at')
            ->take(5)
            ->get();

        return view('driver.dashboard', compact('driver', 'stats', 'activeDeliveries', 'recentCompleted'));
    }

    public function availability(Request $request): RedirectResponse
    {
        $driver = auth()->user()->driver;

        abort_unless($driver?->is_approved && auth()->user()->is_active, 403);

        $data = $request->validate([
            'is_available' => ['required', 'boolean'],
            'availability_note' => ['nullable', 'string', 'max:160'],
        ]);

        $isAvailable = (bool) $data['is_available'];
        $driver->update([
            'is_available' => $isAvailable,
            'availability_note' => $isAvailable ? null : ($data['availability_note'] ?: 'Not accepting new trips'),
            'availability_updated_at' => now(),
        ]);

        return back()->with('status', $isAvailable
            ? 'You are now available for new delivery assignments.'
            : 'You are unavailable for new assignments. Your existing trips remain visible.');
    }
}
