<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class PaymentRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_earlier_checkout_can_complete_after_a_retry_starts(): void
    {
        config(['services.flutterwave.secret_key' => 'test-key']);
        $invoice = $this->invoice();
        $first = $invoice->paymentAttempts()->create(['tx_ref' => 'EDK-OLD', 'amount' => 5000, 'currency' => 'UGX']);
        $invoice->paymentAttempts()->create(['tx_ref' => 'EDK-NEW', 'amount' => 5000, 'currency' => 'UGX']);
        $invoice->update(['payment_reference' => 'EDK-NEW', 'payment_status' => ShoppingList::PAYMENT_PENDING]);
        $this->fakeVerification('101', 'EDK-OLD');

        $this->get(route('website.payments.flutterwave.callback', [
            'status' => 'successful', 'tx_ref' => 'EDK-OLD', 'transaction_id' => '101',
        ]))->assertRedirect();

        $this->assertSame(ShoppingList::PAYMENT_PAID, $invoice->fresh()->payment_status);
        $this->assertSame('EDK-OLD', $invoice->fresh()->payment_reference);
        $this->assertSame('verified', $first->fresh()->status);
    }

    public function test_verified_payment_with_unavailable_stock_is_flagged_for_review(): void
    {
        config(['services.flutterwave.secret_key' => 'test-key']);
        $product = Product::create(['name' => 'Sold out book', 'slug' => 'sold-out-book', 'sku' => 'EDK-SOLD-1', 'price' => 5000, 'is_active' => true]);
        $invoice = $this->invoice([
            'cart_items' => [[
                'product_id' => $product->id, 'name' => $product->name, 'quantity' => 1,
                'unit_price' => 5000, 'fulfilment_source' => 'edukit',
            ]],
        ]);
        $invoice->paymentAttempts()->create(['tx_ref' => 'EDK-STOCK', 'amount' => 5000, 'currency' => 'UGX']);
        $invoice->update(['payment_reference' => 'EDK-STOCK']);
        $this->fakeVerification('102', 'EDK-STOCK');

        $this->get(route('website.payments.flutterwave.callback', [
            'status' => 'successful', 'tx_ref' => 'EDK-STOCK', 'transaction_id' => '102',
        ]))->assertRedirect();

        $this->assertSame(ShoppingList::PAYMENT_PAID, $invoice->fresh()->payment_status);
        $this->assertSame(ShoppingList::STATUS_REVIEWING, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->payment_exception);

        app(InventoryService::class)->recordOpeningStock($product, [
            'warehouse_quantity' => 0, 'display_quantity' => 1,
            'unit_cost' => 3000, 'unit_price' => 5000,
            'occurred_at' => now()->toDateString(),
        ]);
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin)->patch(route('admin.invoices.resolve-paid-stock', $invoice))->assertRedirect();
        $this->assertSame(ShoppingList::STATUS_QUOTED, $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->payment_exception);
        $this->assertSame(0, $product->fresh()->stock_quantity);
    }

    public function test_deleted_legacy_product_is_flagged_instead_of_silently_fulfilled(): void
    {
        config(['services.flutterwave.secret_key' => 'test-key']);
        $invoice = $this->invoice();
        $invoice->lineItems()->create([
            'product_id' => null, 'fulfilment_source' => 'edukit',
            'product_name' => 'Unavailable book', 'quantity' => 1,
            'unit_price' => 5000, 'line_total' => 5000,
        ]);
        $invoice->paymentAttempts()->create(['tx_ref' => 'EDK-MISSING', 'amount' => 5000, 'currency' => 'UGX']);
        $this->fakeVerification('110', 'EDK-MISSING');

        $this->get(route('website.payments.flutterwave.callback', [
            'status' => 'successful', 'tx_ref' => 'EDK-MISSING', 'transaction_id' => '110',
        ]))->assertRedirect();

        $this->assertSame(ShoppingList::PAYMENT_PAID, $invoice->fresh()->payment_status);
        $this->assertSame('stock', $invoice->fresh()->payment_exception_type);
        $this->assertSame(ShoppingList::STATUS_REVIEWING, $invoice->fresh()->status);
    }

    public function test_webhook_requires_signature_and_verifies_transaction(): void
    {
        config(['services.flutterwave.secret_key' => 'test-key', 'services.flutterwave.secret_hash' => 'signed-secret']);
        $invoice = $this->invoice();
        $invoice->paymentAttempts()->create(['tx_ref' => 'EDK-WEBHOOK', 'amount' => 5000, 'currency' => 'UGX']);
        $this->fakeVerification('103', 'EDK-WEBHOOK');

        $url = route('website.payments.flutterwave.webhook');
        $payload = ['data' => ['id' => 103, 'tx_ref' => 'EDK-WEBHOOK']];
        $this->postJson($url, $payload)->assertUnauthorized();
        $this->withHeader('verif-hash', 'signed-secret')->postJson($url, $payload)->assertOk();
        $this->assertSame(ShoppingList::PAYMENT_PAID, $invoice->fresh()->payment_status);
    }

    public function test_successful_provider_payment_with_wrong_amount_is_held_for_review(): void
    {
        config(['services.flutterwave.secret_key' => 'test-key']);
        $invoice = $this->invoice();
        $invoice->paymentAttempts()->create(['tx_ref' => 'EDK-UNDERPAID', 'amount' => 5000, 'currency' => 'UGX']);
        Http::fake([
            'api.flutterwave.com/v3/transactions/104/verify' => Http::response([
                'status' => 'success',
                'data' => ['status' => 'successful', 'tx_ref' => 'EDK-UNDERPAID', 'amount' => 1000, 'currency' => 'UGX'],
            ]),
        ]);

        $this->get(route('website.payments.flutterwave.callback', [
            'status' => 'successful', 'tx_ref' => 'EDK-UNDERPAID', 'transaction_id' => '104',
        ]))->assertRedirect();

        $this->assertSame('verification', $invoice->fresh()->payment_exception_type);
        $this->assertSame(ShoppingList::STATUS_REVIEWING, $invoice->fresh()->status);
        $this->assertNotSame(ShoppingList::PAYMENT_PAID, $invoice->fresh()->payment_status);
    }

    public function test_overpayment_is_flagged_instead_of_fulfilling_the_invoice(): void
    {
        config(['services.flutterwave.secret_key' => 'test-key']);
        $invoice = $this->invoice();
        $invoice->paymentAttempts()->create(['tx_ref' => 'EDK-OVERPAID', 'amount' => 5000, 'currency' => 'UGX']);
        $this->fakeVerification('105', 'EDK-OVERPAID', 6000);

        $this->get(route('website.payments.flutterwave.callback', [
            'status' => 'successful', 'tx_ref' => 'EDK-OVERPAID', 'transaction_id' => '105',
        ]))->assertRedirect();

        $this->assertSame('verification', $invoice->fresh()->payment_exception_type);
        $this->assertNotSame(ShoppingList::PAYMENT_PAID, $invoice->fresh()->payment_status);
        $this->withSession(['invoice_access.'.$invoice->id => true])
            ->get(route('website.quote.show', $invoice->reference))
            ->assertOk()
            ->assertSee('Payment details need review.')
            ->assertDontSee('Payment was received.');
    }

    public function test_late_successful_payment_on_closed_order_is_paid_but_flagged(): void
    {
        config(['services.flutterwave.secret_key' => 'test-key']);
        $invoice = $this->invoice([
            'status' => ShoppingList::STATUS_CANCELLED,
            'payment_status' => ShoppingList::PAYMENT_FAILED,
        ]);
        $invoice->paymentAttempts()->create(['tx_ref' => 'EDK-LATE', 'amount' => 5000, 'currency' => 'UGX']);
        $this->fakeVerification('106', 'EDK-LATE');

        $this->get(route('website.payments.flutterwave.callback', [
            'status' => 'successful', 'tx_ref' => 'EDK-LATE', 'transaction_id' => '106',
        ]))->assertRedirect();

        $this->assertSame(ShoppingList::PAYMENT_PAID, $invoice->fresh()->payment_status);
        $this->assertSame(ShoppingList::STATUS_CANCELLED, $invoice->fresh()->status);
        $this->assertSame('late_payment', $invoice->fresh()->payment_exception_type);
    }

    public function test_another_transaction_on_verified_checkout_is_flagged_as_duplicate(): void
    {
        config(['services.flutterwave.secret_key' => 'test-key']);
        $invoice = $this->invoice();
        $attempt = $invoice->paymentAttempts()->create(['tx_ref' => 'EDK-SAME', 'amount' => 5000, 'currency' => 'UGX']);
        Http::fake([
            'api.flutterwave.com/v3/transactions/107/verify' => Http::response([
                'status' => 'success', 'data' => ['status' => 'successful', 'tx_ref' => 'EDK-SAME', 'amount' => 5000, 'currency' => 'UGX'],
            ]),
            'api.flutterwave.com/v3/transactions/108/verify' => Http::response([
                'status' => 'success', 'data' => ['status' => 'successful', 'tx_ref' => 'EDK-SAME', 'amount' => 5000, 'currency' => 'UGX'],
            ]),
        ]);

        foreach (['107', '108'] as $id) {
            $this->get(route('website.payments.flutterwave.callback', [
                'status' => 'successful', 'tx_ref' => 'EDK-SAME', 'transaction_id' => $id,
            ]))->assertRedirect();
        }

        $this->assertSame('107', $attempt->fresh()->provider_transaction_id);
        $this->assertSame('duplicate', $invoice->fresh()->payment_exception_type);
        $this->assertStringContainsString('108', $invoice->fresh()->payment_exception);
    }

    public function test_mismatched_notification_does_not_change_paid_order_status(): void
    {
        config(['services.flutterwave.secret_key' => 'test-key']);
        $invoice = $this->invoice([
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'paid_at' => now(),
        ]);
        $invoice->paymentAttempts()->create(['tx_ref' => 'EDK-PAID', 'amount' => 5000, 'currency' => 'UGX']);
        $this->fakeVerification('109', 'EDK-PAID', 1000);

        $this->get(route('website.payments.flutterwave.callback', [
            'status' => 'successful', 'tx_ref' => 'EDK-PAID', 'transaction_id' => '109',
        ]))->assertRedirect();

        $this->assertSame(ShoppingList::PAYMENT_PAID, $invoice->fresh()->payment_status);
        $this->assertSame(ShoppingList::STATUS_QUOTED, $invoice->fresh()->status);
        $this->assertSame('verification', $invoice->fresh()->payment_exception_type);
    }

    private function invoice(array $overrides = []): ShoppingList
    {
        return ShoppingList::create($overrides + [
            'parent_name' => 'Customer', 'phone' => '0700000000',
            'source' => ShoppingList::SOURCE_CART, 'items_subtotal' => 5000,
            'estimated_total' => 5000, 'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PENDING,
        ]);
    }

    private function fakeVerification(string $id, string $txRef, int $amount = 5000): void
    {
        Http::fake([
            "api.flutterwave.com/v3/transactions/{$id}/verify" => Http::response([
                'status' => 'success',
                'data' => ['status' => 'successful', 'tx_ref' => $txRef, 'amount' => $amount, 'currency' => 'UGX'],
            ]),
        ]);
    }
}
