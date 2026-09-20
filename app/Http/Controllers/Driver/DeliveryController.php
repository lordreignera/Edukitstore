<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->when($status === 'ready', fn ($query) => $query->where('payment_status', ShoppingList::PAYMENT_PAID)->whereNull('delivery_confirmed_at'))
            ->when($status === 'waiting', fn ($query) => $query->where('payment_status', '!=', ShoppingList::PAYMENT_PAID)->whereNull('delivery_confirmed_at'))
            ->when($status === 'active', fn ($query) => $query->whereNull('delivery_confirmed_at'))
            ->when($status === 'completed', fn ($query) => $query->whereNotNull('delivery_confirmed_at'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('driver.deliveries.index', compact('driver', 'deliveries', 'search', 'status'));
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

        DB::transaction(function () use ($shoppingList, $data): void {
            $shoppingList->update([
                'status' => ShoppingList::STATUS_FULFILLED,
                'delivery_confirmed_at' => now(),
                'delivery_confirmed_by' => auth()->id(),
                'delivery_notes' => $data['delivery_notes'] ?? null,
            ]);
            $shoppingList->lineItems()->whereNotNull('supplier_id')->update(['settlement_status' => 'earned']);
        });

        return back()->with('status', 'Delivery confirmed. This transaction is now complete.');
    }
}
