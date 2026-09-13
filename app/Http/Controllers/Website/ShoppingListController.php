<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\ShoppingList;
use App\Support\DocumentStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShoppingListController extends Controller
{
    public function create(): View
    {
        return view('website.upload-list', [
            'schools' => School::query()
                ->with('district')
                ->active()
                ->whereHas('district', fn ($query) => $query->active())
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'parent_name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'school_id' => ['nullable', 'required_if:delivery_preference,school', Rule::exists('schools', 'id')->where('is_active', true)],
            'learner_name' => ['nullable', 'required_if:delivery_preference,school', 'string', 'max:160'],
            'class_level' => ['nullable', 'required_if:delivery_preference,school', 'string', 'max:80'],
            'delivery_location' => ['nullable', 'string', 'max:255'],
            'delivery_preference' => ['required', 'in:school,pickup'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shopping_list' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'max:8192'],
        ]);

        $school = null;
        $deliveryFee = 0;

        if ($data['delivery_preference'] === 'school') {
            $school = School::query()
                ->with('district')
                ->active()
                ->whereHas('district', fn ($query) => $query->active())
                ->findOrFail($data['school_id']);

            $deliveryFee = $school->delivery_fee;
            $data['district_id'] = $school->district_id;
            $data['school_id'] = $school->id;
            $data['school_name'] = $school->name;
            $data['delivery_location'] = $school->location ?: $school->district?->name;
        } else {
            $data['district_id'] = null;
            $data['school_id'] = null;
            $data['school_name'] = 'Warehouse pickup';
            $data['delivery_location'] = $data['delivery_location'] ?: 'EduKit warehouse pickup';
        }

        $file = $request->file('shopping_list');

        $data['file_path'] = $file->store('shopping-lists', DocumentStorage::disk());
        $data['original_filename'] = $file->getClientOriginalName();
        $data['source'] = ShoppingList::SOURCE_UPLOAD;
        $data['reference'] = ShoppingList::nextReference();
        $data['status'] = ShoppingList::STATUS_PENDING;
        $data['delivery_fee'] = $deliveryFee;

        unset($data['shopping_list']);

        $shoppingList = ShoppingList::create($data + [
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);

        return redirect()
            ->route('website.quote.show', $shoppingList->reference)
            ->with('status', 'Your school list has been uploaded. EduKit will review it and prepare your invoice.');
    }
}
