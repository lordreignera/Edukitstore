<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Support\InvoiceAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $email = strtolower($contact);
        $phoneDigits = preg_replace('/\D+/', '', $contact);
        $phoneCandidates = array_values(array_unique(array_filter([
            $contact,
            $phoneDigits,
            $phoneDigits !== '' ? '+'.$phoneDigits : null,
            str_starts_with($phoneDigits, '256') ? '0'.substr($phoneDigits, 3) : null,
            str_starts_with($phoneDigits, '0') ? '256'.substr($phoneDigits, 1) : null,
            str_starts_with($phoneDigits, '0') ? '+256'.substr($phoneDigits, 1) : null,
        ])));

        $invoice = ShoppingList::query()
            ->where(function ($query) use ($reference) {
                $query->where('reference', $reference)
                    ->orWhere('payment_reference', $reference);
            })
            ->where(function ($query) use ($phoneCandidates, $email) {
                if ($phoneCandidates !== []) {
                    $query->whereIn('phone', $phoneCandidates);
                }
                $query->orWhereRaw('LOWER(email) = ?', [$email]);
            })
            ->first();

        if (! $invoice) {
            return back()
                ->withErrors(['reference' => 'We could not find an invoice with that reference and phone/email.'])
                ->withInput();
        }

        InvoiceAccess::grant($invoice);

        return redirect()->route('website.quote.show', $invoice->reference);
    }

    public function acknowledgeReceipt(Request $request, string $reference): RedirectResponse
    {
        $invoice = ShoppingList::where('reference', strtoupper(trim($reference)))
            ->orWhere('payment_reference', strtoupper(trim($reference)))
            ->firstOrFail();
        InvoiceAccess::check($invoice);

        if ($invoice->customer_received_at || $invoice->delivery_confirmed_at) {
            return back()->with('status', 'Receipt was already confirmed for this order.');
        }

        abort_unless($invoice->payment_status === ShoppingList::PAYMENT_PAID, 404);
        abort_unless($invoice->driver_reached_at, 404);

        $data = $request->validate([
            'items_received' => ['accepted'],
            'received_name' => ['required', 'string', 'max:160'],
            'received_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($invoice, $data): void {
            $invoice->update([
                'status' => ShoppingList::STATUS_FULFILLED,
                'customer_received_at' => now(),
                'customer_received_name' => $data['received_name'],
                'customer_received_notes' => $data['received_notes'] ?? null,
                // Keep the existing completion fields populated for older reports.
                'delivery_confirmed_at' => now(),
                'delivery_notes' => $data['received_notes'] ?? $invoice->delivery_notes,
            ]);
            $invoice->lineItems()->whereNotNull('supplier_id')->update(['settlement_status' => 'earned']);
        });

        return redirect()->route('website.quote.show', $invoice->reference)
            ->with('status', 'Receipt confirmed. Thank you for confirming that all items were received.');
    }
}
