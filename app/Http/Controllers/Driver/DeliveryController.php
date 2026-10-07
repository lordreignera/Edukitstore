<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $driver = auth()->user()->driver;

        abort_unless($driver?->is_approved, 403);

        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');

        $deliveries = ShoppingList::query()
            ->with('school.district')
            ->where('assigned_driver_id', $driver->id)
            ->when($search !== '', fn ($query) => $query->where(function ($deliveryQuery) use ($search) {
                $deliveryQuery->where('reference', 'like', "%{$search}%")
                    ->orWhere('parent_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($status === 'ready', fn ($query) => $query->where('payment_status', ShoppingList::PAYMENT_PAID)->where('status', ShoppingList::STATUS_QUOTED)->whereNull('payment_exception')->whereNull('driver_started_at')->whereNull('customer_received_at'))
            ->when($status === 'waiting', fn ($query) => $query->where('payment_status', '!=', ShoppingList::PAYMENT_PAID)->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($status === 'in_transit', fn ($query) => $query->whereNotNull('driver_started_at')->whereNull('driver_reached_at')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($status === 'reached', fn ($query) => $query->whereNotNull('driver_reached_at')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($status === 'active', fn ($query) => $query->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($status === 'completed', fn ($query) => $query->where(function ($completedQuery) {
                $completedQuery->whereNotNull('customer_received_at')->orWhereNotNull('delivery_confirmed_at');
            }))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('driver.deliveries.index', compact('driver', 'deliveries', 'search', 'status'));
    }

    public function startJourney(ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorizeDriver($shoppingList);

        $error = $this->validateJourneyOrder($shoppingList);
        if ($error) {
            return back()->withErrors(['delivery' => $error]);
        }

        if ($shoppingList->driver_started_at) {
            return back()->with('status', 'Journey already started.');
        }

        $shoppingList->update([
            'driver_started_at' => now(),
            'driver_started_by' => auth()->id(),
        ]);

        return back()->with('status', 'Journey started. The customer can now see that the order is in transit.');
    }

    public function reached(Request $request, ShoppingList $shoppingList): RedirectResponse
    {
        $this->authorizeDriver($shoppingList);

        $error = $this->validateJourneyOrder($shoppingList);
        if ($error) {
            return back()->withErrors(['delivery' => $error]);
        }

        if (! $shoppingList->driver_started_at) {
            return back()->withErrors(['delivery' => 'Start the journey before marking that you have reached the destination.']);
        }

        if ($shoppingList->driver_reached_at) {
            return back()->with('status', 'Arrival was already recorded.');
        }

        $data = $request->validate([
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shoppingList->update([
            'driver_reached_at' => now(),
            'driver_reached_by' => auth()->id(),
            'delivery_notes' => $data['delivery_notes'] ?? null,
        ]);

        return back()->with('status', 'Arrival recorded. The customer can now confirm receipt of the items.');
    }

    private function authorizeDriver(ShoppingList $shoppingList): void
    {
        $driver = auth()->user()->driver;

        abort_unless($driver?->is_approved, 403);
        abort_unless($shoppingList->assigned_driver_id === $driver->id, 403);
    }

    private function validateJourneyOrder(ShoppingList $shoppingList): ?string
    {
        if ($shoppingList->payment_status !== ShoppingList::PAYMENT_PAID) {
            return 'The customer payment must be verified before starting delivery.';
        }

        if ($shoppingList->status === ShoppingList::STATUS_CANCELLED) {
            return 'Cancelled orders cannot be delivered.';
        }

        if ($shoppingList->status !== ShoppingList::STATUS_QUOTED || $shoppingList->payment_exception) {
            return 'This order is under review and cannot move through delivery yet.';
        }

        if ($shoppingList->customer_received_at || $shoppingList->delivery_confirmed_at) {
            return 'The customer has already confirmed receipt for this order.';
        }

        return null;
    }
}
