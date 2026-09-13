<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(): View
    {
        $driver = auth()->user()->driver;

        abort_unless($driver?->is_approved, 403);

        $deliveries = ShoppingList::query()
            ->with('school.district')
            ->where('assigned_driver_id', $driver->id)
            ->latest()
            ->paginate(12);

        return view('driver.deliveries.index', compact('driver', 'deliveries'));
    }

    public function confirm(Request $request, ShoppingList $shoppingList): RedirectResponse
    {
        $driver = auth()->user()->driver;

        abort_unless($driver?->is_approved, 403);
        abort_unless($shoppingList->assigned_driver_id === $driver->id, 403);

        if ($shoppingList->payment_status !== ShoppingList::PAYMENT_PAID) {
            return back()->withErrors(['delivery' => 'Delivery can only be confirmed after the customer payment is verified.']);
        }

        if ($shoppingList->status === ShoppingList::STATUS_CANCELLED) {
            return back()->withErrors(['delivery' => 'Cancelled requests cannot be confirmed as delivered.']);
        }

        if ($shoppingList->delivery_confirmed_at) {
            return back()->with('status', 'Delivery was already confirmed.');
        }

        $data = $request->validate([
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shoppingList->update([
            'status' => ShoppingList::STATUS_FULFILLED,
            'delivery_confirmed_at' => now(),
            'delivery_confirmed_by' => auth()->id(),
            'delivery_notes' => $data['delivery_notes'] ?? null,
        ]);

        return back()->with('status', 'Delivery confirmed. This transaction is now complete.');
    }
}
