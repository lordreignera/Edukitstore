<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Rules\UgandaMobileMoneyNumber;
use App\Services\SchoolDeliveryService;
use App\Services\OrderNotificationService;
use App\Support\DocumentStorage;
use App\Support\InvoiceAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShoppingListController extends Controller
{
    public function create(SchoolDeliveryService $delivery): View
    {
        return view('website.upload-list', [
            'schools' => $delivery->activeSchools(),
        ]);
    }

    public function store(Request $request, SchoolDeliveryService $delivery, OrderNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'parent_name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'min:7', 'max:40', 'regex:/^[0-9+\\s().-]+$/', new UgandaMobileMoneyNumber],
            'email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shopping_list' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'max:8192'],
        ] + $delivery->validationRules());

        $data = $delivery->applyTo($data);
        $data['phone'] = UgandaMobileMoneyNumber::normalize($data['phone']);
        $file = $request->file('shopping_list');

        $data['file_path'] = $file->store('shopping-lists', DocumentStorage::disk());
        $data['original_filename'] = $file->getClientOriginalName();
        $data['source'] = ShoppingList::SOURCE_UPLOAD;
        $data['customer_user_id'] = auth()->user()?->hasRole('customer') ? auth()->id() : null;
        $data['reference'] = ShoppingList::nextReference();
        $data['status'] = ShoppingList::STATUS_PENDING;
        $data['email'] = Str::lower(trim($data['email']));

        unset($data['shopping_list']);

        $shoppingList = ShoppingList::create($data + [
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);
        InvoiceAccess::grant($shoppingList);
        $notifications->orderReceived($shoppingList);

        return redirect()
            ->route('website.quote.show', $shoppingList->reference)
            ->with('status', 'Your school list has been uploaded. EduKit will review it and prepare your invoice.');
    }
}
