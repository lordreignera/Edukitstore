<?php

namespace App\Services;

use App\Models\PaymentAttempt;
use App\Models\ShoppingList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;

class FlutterwavePaymentService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly SupplierSaleService $supplierSales,
    ) {}

    public function attempt(string $txRef): PaymentAttempt
    {
        $attempt = PaymentAttempt::where('tx_ref', $txRef)->first();
        if ($attempt) {
            return $attempt;
        }

        // Payments initiated before payment_attempts existed remain verifiable.
        $invoice = ShoppingList::where('payment_reference', $txRef)->firstOrFail();
        return $invoice->paymentAttempts()->firstOrCreate(
            ['tx_ref' => $txRef],
            ['amount' => $invoice->estimated_total, 'currency' => 'UGX', 'status' => 'initiated']
        );
    }

    public function markFailed(PaymentAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt): void {
            $invoice = ShoppingList::whereKey($attempt->shopping_list_id)->lockForUpdate()->firstOrFail();
            if ($invoice->payment_status === ShoppingList::PAYMENT_PAID) {
                return;
            }

            $attempt->update(['status' => 'failed']);
            if ($invoice->payment_reference === $attempt->tx_ref) {
                $invoice->update(['payment_status' => ShoppingList::PAYMENT_FAILED]);
            }
        });
    }

    public function verifyAndRecord(PaymentAttempt $attempt, string $transactionId): bool
    {
        $secretKey = config('services.flutterwave.secret_key');
        if (! $secretKey || ! ctype_digit($transactionId)) {
            return false;
        }

        try {
            $response = Http::withToken($secretKey)->acceptJson()->timeout(20)
                ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");
        } catch (ConnectionException $exception) {
            report($exception);

            return false;
        }
        $data = $response->json('data', []);

        $matchedReference = $response->successful()
            && $response->json('status') === 'success'
            && ($data['status'] ?? null) === 'successful'
            && ($data['tx_ref'] ?? null) === $attempt->tx_ref;
        if (! $matchedReference) {
            return false;
        }

        $matchedAmountAndCurrency = strtoupper((string) ($data['currency'] ?? '')) === $attempt->currency
            && is_numeric($data['amount'] ?? null)
            && (float) $data['amount'] === (float) $attempt->amount;

        return DB::transaction(function () use ($attempt, $transactionId, $matchedAmountAndCurrency): bool {
            $invoice = ShoppingList::whereKey($attempt->shopping_list_id)->lockForUpdate()->firstOrFail();
            $attempt = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if (PaymentAttempt::where('provider_transaction_id', $transactionId)->whereKeyNot($attempt->id)->exists()) {
                throw ValidationException::withMessages(['payment' => 'This transaction has already been recorded.']);
            }

            if (! $matchedAmountAndCurrency) {
                if (! $attempt->provider_transaction_id) {
                    $attempt->update(['status' => 'verification_mismatch', 'provider_transaction_id' => $transactionId]);
                }
                $invoice->update([
                    'payment_exception' => "Flutterwave transaction {$transactionId} has an amount or currency mismatch. Verify it before taking further action.",
                    'payment_exception_type' => 'verification',
                    'status' => $invoice->payment_status === ShoppingList::PAYMENT_PAID
                        ? $invoice->status : ShoppingList::STATUS_REVIEWING,
                ]);

                return true;
            }

            if ($attempt->status === 'verified' && $invoice->payment_status === ShoppingList::PAYMENT_PAID) {
                if ($attempt->provider_transaction_id === $transactionId) {
                    return true;
                }

                $invoice->update([
                    'payment_exception' => "Another successful Flutterwave transaction ({$transactionId}) used this checkout reference. Review and refund any duplicate charge.",
                    'payment_exception_type' => 'duplicate',
                ]);

                return true;
            }

            if ($invoice->payment_status === ShoppingList::PAYMENT_PAID) {
                $attempt->update(['status' => 'duplicate_payment', 'provider_transaction_id' => $transactionId, 'verified_at' => now()]);
                $invoice->update([
                    'payment_exception' => 'A second payment was received. Review and refund the duplicate transaction.',
                    'payment_exception_type' => 'duplicate',
                ]);

                return true;
            }

            if (in_array($invoice->status, [ShoppingList::STATUS_CANCELLED, ShoppingList::STATUS_REJECTED, ShoppingList::STATUS_FULFILLED], true)) {
                $attempt->update(['status' => 'verified', 'provider_transaction_id' => $transactionId, 'verified_at' => now()]);
                $invoice->update([
                    'payment_status' => ShoppingList::PAYMENT_PAID,
                    'payment_provider' => 'flutterwave',
                    'payment_reference' => $attempt->tx_ref,
                    'paid_at' => now(),
                    'payment_exception' => 'Payment arrived after this order was closed. Review the transaction and arrange a refund or a new order.',
                    'payment_exception_type' => 'late_payment',
                ]);

                return true;
            }

            $exception = null;
            try {
                DB::transaction(function () use ($invoice): void {
                    $this->inventory->recordPaidCartSale($invoice);
                    $this->supplierSales->recordPaidSale($invoice);
                });
            } catch (ValidationException $error) {
                $exception = 'Payment verified, but stock could not be allocated: '.$error->getMessage();
            }

            $attempt->update(['status' => 'verified', 'provider_transaction_id' => $transactionId, 'verified_at' => now()]);
            $invoice->update([
                'payment_status' => ShoppingList::PAYMENT_PAID,
                'payment_provider' => 'flutterwave',
                'payment_reference' => $attempt->tx_ref,
                'paid_at' => now(),
                'payment_exception' => $exception,
                'payment_exception_type' => $exception ? 'stock' : null,
                'status' => $exception ? ShoppingList::STATUS_REVIEWING : $invoice->status,
            ]);

            return true;
        });
    }
}
