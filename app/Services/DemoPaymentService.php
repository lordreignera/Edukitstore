<?php

namespace App\Services;

use App\Models\ShoppingList;
use App\Support\PaymentMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DemoPaymentService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly SupplierSaleService $supplierSales,
        private readonly OrderNotificationService $notifications,
    ) {}

    public function pay(ShoppingList $invoice): void
    {
        abort_unless(PaymentMode::demoEnabled(), 404);

        // Dry-run payments share the live stock, cost and margin path, but never call a gateway.
        // Locking the invoice and writing everything in one transaction prevents duplicate payment records/sales.
        DB::transaction(function () use ($invoice): void {
            $invoice = ShoppingList::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status !== ShoppingList::STATUS_QUOTED
                || $invoice->payment_status === ShoppingList::PAYMENT_PAID
                || $invoice->estimated_total < 1) {
                throw ValidationException::withMessages(['payment' => 'This invoice is not ready for a demo payment.']);
            }

            $this->inventory->ensureInvoiceHasDisplayStock($invoice);
            $this->supplierSales->ensureStock($invoice);
            $this->inventory->recordPaidCartSale($invoice);
            $this->supplierSales->recordPaidSale($invoice);

            $reference = 'DEMO-'.$invoice->id.'-'.Str::upper(Str::random(10));
            $invoice->paymentAttempts()->create([
                'tx_ref' => $reference,
                'amount' => $invoice->estimated_total,
                'currency' => 'UGX',
                'status' => 'demo_paid',
                'verified_at' => now(),
            ]);
            $invoice->update([
                'payment_status' => ShoppingList::PAYMENT_PAID,
                'payment_provider' => 'demo',
                'payment_reference' => $reference,
                'paid_at' => now(),
            ]);
        });

        // Notify only after the payment transaction commits; a rolled-back payment must not send a receipt.
        $this->notifications->paymentConfirmed($invoice->fresh());
    }
}
