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
        return view('website.track-order', [
            'matches' => collect(),
            'recoveryContact' => null,
        ]);
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
            ->where(function ($query) use ($reference) {
                $query->where('reference', $reference)
                    ->orWhere('payment_reference', $reference);
            })
            ->where(fn ($query) => $this->whereContactMatches($query, $contact))
            ->first();

        if (! $invoice) {
            return back()
                ->withErrors(['reference' => 'We could not find an invoice with that reference and phone/email.'])
                ->withInput();
        }

        InvoiceAccess::grant($invoice);

        return redirect()->route('website.quote.show', $invoice->reference);
    }

    public function recover(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'contact' => ['required', 'string', 'max:255'],
        ]);
        $contact = trim($data['contact']);
        $matches = ShoppingList::query()
            ->withCount('lineItems')
            ->where(fn ($query) => $this->whereContactMatches($query, $contact))
            ->latest('created_at')
            ->get();

        if ($matches->isEmpty()) {
            return back()
                ->withErrors(['contact' => 'We could not find orders for that phone number or email.'])
                ->withInput();
        }

        $matches->each(function (ShoppingList $invoice): void {
            InvoiceAccess::grant($invoice);
        });

        return view('website.track-order', [
            'matches' => $matches,
            'recoveryContact' => $contact,
        ]);
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

    private function whereContactMatches($query, string $contact): void
    {
        $email = strtolower(trim($contact));
        $phoneCandidates = $this->phoneCandidates($contact);
        $normalizedPhoneCandidates = array_values(array_unique(array_filter(array_map(
            fn (string $phone): string => preg_replace('/\D+/', '', $phone),
            $phoneCandidates,
        ))));

        $query->where(function ($contactQuery) use ($phoneCandidates, $normalizedPhoneCandidates, $email): void {
            if ($phoneCandidates !== []) {
                $contactQuery->whereIn('phone', $phoneCandidates);
            }
            if ($normalizedPhoneCandidates !== []) {
                $phoneExpression = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', '')";
                $contactQuery->orWhere(function ($phoneQuery) use ($phoneExpression, $normalizedPhoneCandidates): void {
                    foreach ($normalizedPhoneCandidates as $normalizedPhone) {
                        $phoneQuery->orWhereRaw($phoneExpression.' = ?', [$normalizedPhone]);
                    }
                });
            }
            $contactQuery->orWhereRaw('LOWER(email) = ?', [$email]);
        });
    }

    private function phoneCandidates(string $contact): array
    {
        $phoneDigits = preg_replace('/\D+/', '', trim($contact));

        return array_values(array_unique(array_filter([
            trim($contact),
            $phoneDigits,
            $phoneDigits !== '' ? '+'.$phoneDigits : null,
            str_starts_with($phoneDigits, '256') ? '0'.substr($phoneDigits, 3) : null,
            str_starts_with($phoneDigits, '0') ? '256'.substr($phoneDigits, 1) : null,
            str_starts_with($phoneDigits, '0') ? '+256'.substr($phoneDigits, 1) : null,
        ])));
    }
}
