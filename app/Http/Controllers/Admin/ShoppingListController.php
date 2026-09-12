<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShoppingListController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');
        $delivery = (string) $request->query('delivery');

        $shoppingLists = ShoppingList::query()
            ->with('assignedDriver')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($listQuery) use ($search) {
                    $listQuery->where('parent_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('school_name', 'like', "%{$search}%")
                        ->orWhere('learner_name', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($status, ShoppingList::statuses()), fn ($query) => $query->where('status', $status))
            ->when(in_array($delivery, ['school', 'home'], true), fn ($query) => $query->where('delivery_preference', $delivery))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.shopping-lists.index', [
            'shoppingLists' => $shoppingLists,
            'statuses' => ShoppingList::statuses(),
            'search' => $search,
            'selectedStatus' => $status,
            'delivery' => $delivery,
        ]);
    }

    public function show(ShoppingList $shoppingList): View
    {
        return view('admin.shopping-lists.show', [
            'shoppingList' => $shoppingList->load('assignedDriver'),
            'statuses' => ShoppingList::statuses(),
            'drivers' => Driver::query()
                ->where('is_approved', true)
                ->where('is_available', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, ShoppingList $shoppingList): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(ShoppingList::statuses()))],
            'estimated_total' => ['nullable', 'integer', 'min:0'],
            'delivery_fee' => ['nullable', 'integer', 'min:0'],
            'assigned_driver_id' => ['nullable', 'exists:drivers,id'],
        ]);

        if ($data['status'] === ShoppingList::STATUS_FULFILLED && ! $shoppingList->delivery_confirmed_at) {
            return back()->withErrors(['status' => 'A transaction can only be fulfilled after the assigned driver confirms delivery.'])->withInput();
        }

        if ($data['status'] === ShoppingList::STATUS_QUOTED && ! $data['assigned_driver_id'] && ! $shoppingList->assigned_driver_id) {
            return back()->withErrors(['assigned_driver_id' => 'Assign an approved driver before releasing the invoice.'])->withInput();
        }

        if ($data['assigned_driver_id']) {
            $driverIsAssignable = Driver::whereKey($data['assigned_driver_id'])
                ->where('is_approved', true)
                ->where('is_available', true)
                ->exists();

            if (! $driverIsAssignable) {
                return back()->withErrors(['assigned_driver_id' => 'Choose an approved and available driver.'])->withInput();
            }
        }

        if ($shoppingList->source === ShoppingList::SOURCE_CART) {
            $data['estimated_total'] = $data['delivery_fee'] === null
                ? null
                : $shoppingList->items_subtotal + (int) $data['delivery_fee'];
        }

        $shoppingList->update($data + [
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('status', 'Shopping list updated.');
    }

    public function download(ShoppingList $shoppingList): StreamedResponse
    {
        abort_unless($shoppingList->file_path, 404);
        abort_unless(Storage::exists($shoppingList->file_path), 404);

        return Storage::download($shoppingList->file_path, $shoppingList->original_filename);
    }
}
