<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Services\OrderNotificationService;
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

    public function acknowledgeReceipt(Request $request, string $reference, OrderNotificationService $notifications): RedirectResponse
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
            // Customer receipt is the completion event; supplier payables are earned only after this confirmation.
            $invoice->update([
                'status' => ShoppingList::STATUS_FULFILLED,
                'customer_received_at' => now(),
                'customer_received_name' => $data['received_name'],
                'customer_received_notes' => $data['received_notes'] ?? null,
                // Keep the existing completion fields populated for older reports.
                'delivery_confirmed_at' => now(),
                'delivery_notes' => $data['received_notes'] ?? $invoice->delivery_notes,
            ]);
            $invoice->lineItems()->whereNotNull('supplier_id')->where('supplier_payable', '>', 0)->update(['settlement_status' => 'earned']);
        });
        $notifications->customerConfirmedReceipt($invoice->fresh());

        return redirect()->route('website.quote.show', $invoice->reference)
            ->with('status', 'Receipt confirmed. Thank you for confirming that all items were received.');
    }

}
