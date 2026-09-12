<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrackOrderController extends Controller
{
    public function index(): View
    {
        return view('website.track-order');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
            'contact' => ['required', 'string', 'max:255'],
        ]);

        $reference = strtoupper(trim($data['reference']));
        $contact = trim($data['contact']);

        $invoice = ShoppingList::query()
            ->where('reference', $reference)
            ->where(function ($query) use ($contact) {
                $query->where('phone', $contact)
                    ->orWhere('email', $contact);
            })
            ->first();

        if (! $invoice) {
            return back()
                ->withErrors(['reference' => 'We could not find an invoice with that reference and phone/email.'])
                ->withInput();
        }

        return redirect()->route('website.quote.show', $invoice->reference);
    }
}
