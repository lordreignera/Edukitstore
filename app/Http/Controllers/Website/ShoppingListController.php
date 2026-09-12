<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShoppingListController extends Controller
{
    public function create(): View
    {
        return view('website.upload-list');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'parent_name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'school_name' => ['nullable', 'string', 'max:160'],
            'learner_name' => ['nullable', 'string', 'max:160'],
            'class_level' => ['nullable', 'string', 'max:80'],
            'delivery_location' => ['nullable', 'string', 'max:255'],
            'delivery_preference' => ['required', 'in:school,home,pickup'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shopping_list' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'max:8192'],
        ]);

        $file = $request->file('shopping_list');

        $data['file_path'] = $file->store('shopping-lists');
        $data['original_filename'] = $file->getClientOriginalName();
        $data['source'] = ShoppingList::SOURCE_UPLOAD;
        $data['reference'] = $this->uniqueReference();
        $data['status'] = ShoppingList::STATUS_PENDING;

        unset($data['shopping_list']);

        $shoppingList = ShoppingList::create($data + [
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);

        return redirect()
            ->route('website.quote.show', $shoppingList->reference)
            ->with('status', 'Your school list has been uploaded. EduKit will review it and prepare your invoice.');
    }

    private function uniqueReference(): string
    {
        do {
            $reference = 'EDK-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (ShoppingList::where('reference', $reference)->exists());

        return $reference;
    }
}
