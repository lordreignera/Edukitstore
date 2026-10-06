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
    ) {}

    public function pay(ShoppingList $invoice): void
    {
        abort_unless(PaymentMode::demoEnabled(), 404);

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
    }
}
