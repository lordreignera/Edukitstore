<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShoppingList;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DemoPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_payment_requires_item_confirmation_and_records_stock_once_without_provider_call(): void
    {
        config(['edukit.payment_mode' => 'demo', 'app.env' => 'testing']);
        Http::fake();

        $product = Product::create([
            'name' => 'School Shoes', 'slug' => 'school-shoes', 'sku' => 'SHOE-1',
            'price' => 45000, 'is_active' => true,
        ]);
        app(InventoryService::class)->recordOpeningStock($product, [
            'warehouse_quantity' => 0, 'display_quantity' => 2,
            'unit_cost' => 30000, 'unit_price' => 45000,
            'occurred_at' => now()->toDateString(),
        ]);
        $invoice = ShoppingList::create([
            'parent_name' => 'Demo Buyer', 'phone' => '+256700123456',
            'source' => ShoppingList::SOURCE_CART,
            'delivery_preference' => 'school',
            'cart_items' => [[
                'product_id' => $product->id, 'name' => $product->name,
                'sku' => $product->sku, 'fulfilment_source' => 'edukit',
                'quantity' => 1, 'unit_price' => 45000, 'line_total' => 45000,
            ]],
            'items_subtotal' => 45000, 'delivery_fee' => 5000,
            'estimated_total' => 50000,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);
        $session = ['invoice_access' => [$invoice->id => true]];

        $this->withSession($session)->post(route('website.quote.pay', $invoice->reference))
            ->assertSessionHasErrors('confirm_items');
        $this->assertSame(ShoppingList::PAYMENT_UNPAID, $invoice->fresh()->payment_status);

        $this->withSession($session)->post(route('website.quote.pay', $invoice->reference), ['confirm_items' => '1'])
            ->assertRedirect(route('website.quote.show', $invoice->reference));

        $this->assertSame(ShoppingList::PAYMENT_PAID, $invoice->fresh()->payment_status);
        $this->assertSame('demo', $invoice->fresh()->payment_provider);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame(1, $invoice->paymentAttempts()->count());
        Http::assertNothingSent();

        $this->withSession($session)->post(route('website.quote.pay', $invoice->reference), ['confirm_items' => '1'])
            ->assertNotFound();
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame(1, $invoice->paymentAttempts()->count());
    }

    public function test_demo_mode_is_disabled_in_production(): void
    {
        config(['edukit.payment_mode' => 'demo', 'app.env' => 'production']);

        $this->assertFalse(\App\Support\PaymentMode::demoEnabled());
    }
}
