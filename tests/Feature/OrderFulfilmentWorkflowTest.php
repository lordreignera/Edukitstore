<?php

namespace Tests\Feature;

use App\Mail\OrderFulfilmentUpdateMail;
use App\Models\Driver;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\DemoPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class OrderFulfilmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_screen_shows_the_fulfilment_panel_instead_of_ready_for_payment(): void
    {
        [$admin, $order] = $this->paidOrder();
        $product = Product::create([
            'name' => 'Exercise Book', 'slug' => 'order-screen-exercise-book', 'sku' => 'BOOK-01',
            'price' => 5000, 'cost_price' => 3000, 'image_path' => 'https://images.example.test/exercise-book.jpg', 'is_active' => true,
        ]);
        $order->lineItems()->create([
            'product_id' => $product->id, 'product_name' => 'Exercise Book', 'sku' => 'BOOK-01', 'quantity' => 2,
            'unit_cost' => 3000, 'unit_price' => 5000, 'line_total' => 10000,
            'cost_total' => 6000, 'profit_total' => 4000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.show', $order))
            ->assertOk()
            ->assertSee('Check, pack and assign this order')
            ->assertSee('Availability to check')
            ->assertSee('Pack available items')
            ->assertSee('Step 3')
            ->assertSee('I confirm the available quantities above are packed and ready.')
            ->assertSee('Unavailable qty')
            ->assertSee('Confirm packing &amp; assign driver', false)
            ->assertSee('Mobile Money phone')
            ->assertSee('data-availability-select', false)
            ->assertSee('data-shortage-fields', false)
            ->assertSee('data-shortage-fields style="display: none;"', false)
            ->assertSee('data-product-image-dialog', false)
            ->assertSee('data-image-preview', false)
            ->assertDontSee('Ready for Payment');

        $this->assertLessThan(
            strpos($response->getContent(), 'data-packing-form'),
            strpos($response->getContent(), 'Mobile Money phone'),
            'Customer contact details should appear before the fulfilment form.',
        );
    }

    public function test_paid_order_cannot_be_marked_packed_without_explicit_packing_confirmation(): void
    {
        [$admin, $order] = $this->paidOrder();
        $driver = Driver::create(['name' => 'Amina Driver', 'phone' => '+256700000001', 'is_approved' => true, 'is_available' => true]);
        $line = $order->lineItems()->create([
            'product_name' => 'Exercise Book', 'sku' => 'BOOK-01', 'quantity' => 2,
            'unit_cost' => 3000, 'unit_price' => 5000, 'line_total' => 10000,
            'cost_total' => 6000, 'profit_total' => 4000,
        ]);

        $this->actingAs($admin)->patch(route('admin.invoices.fulfilment', $order), [
            'items' => [$line->id => ['unavailable_quantity' => 0]],
            'assigned_driver_id' => $driver->id,
        ])->assertSessionHasErrors('packed_items_confirmed');

        $this->assertNull($order->fresh()->packed_at);
        $this->assertNull($order->fresh()->assigned_driver_id);
    }

    public function test_admin_can_pack_available_quantities_assign_driver_and_record_partial_manual_refund(): void
    {
        Mail::fake();
        [$admin, $order] = $this->paidOrder(['email' => 'customer@example.test']);
        $driver = Driver::create(['name' => 'Amina Driver', 'phone' => '+256700000001', 'is_approved' => true, 'is_available' => true]);
        $line = $order->lineItems()->create([
            'product_name' => 'Exercise Book', 'sku' => 'BOOK-01', 'quantity' => 2,
            'unit_cost' => 3000, 'unit_price' => 5000, 'line_total' => 10000,
            'cost_total' => 6000, 'profit_total' => 4000,
        ]);

        $this->actingAs($admin)->patch(route('admin.invoices.fulfilment', $order), [
            'items' => [
                $line->id => [
                    'unavailable_quantity' => 1,
                    'unavailable_reason' => 'out_of_stock',
                    'unavailable_note' => 'The second copy was not on the shelf.',
                ],
            ],
            'packed_items_confirmed' => true,
            'assigned_driver_id' => $driver->id,
        ])->assertRedirect();

        $order->refresh();
        $line->refresh();
        $this->assertNotNull($order->packed_at);
        $this->assertSame($driver->id, $order->assigned_driver_id);
        $this->assertSame(2, $line->ordered_quantity);
        $this->assertSame(1, $line->quantity);
        $this->assertSame(1, $line->unavailable_quantity);
        $this->assertSame(5000.0, (float) $line->line_total);
        $this->assertSame(5000, $order->manualRefundDue());
        Mail::assertSent(OrderFulfilmentUpdateMail::class);

        $this->actingAs($admin)->get(route('admin.invoices.show', $order))
            ->assertOk()
            ->assertSee('Availability checked')
            ->assertSee('Items packed')
            ->assertSee('Driver assigned')
            ->assertSee('Fulfilment is saved: 1 unit(s) packed and assigned to Amina Driver.');

        $this->actingAs($admin)->patch(route('admin.invoices.manual-refund', $order), [
            'manual_refund_reference' => 'MOMO-REFUND-1001',
        ])->assertRedirect();

        $this->assertSame(ShoppingList::PAYMENT_PAID, $order->fresh()->payment_status);
        $this->assertSame(5000, $order->fresh()->manual_refund_amount);
        $this->assertSame('MOMO-REFUND-1001', $order->fresh()->manual_refund_reference);
        $this->assertSame(ShoppingList::STATUS_QUOTED, $order->fresh()->status);
        Mail::assertSentTimes(OrderFulfilmentUpdateMail::class, 2);
    }

    public function test_all_unavailable_order_includes_fee_in_refund_and_closes_after_recording_it(): void
    {
        Mail::fake();
        [$admin, $order] = $this->paidOrder(['delivery_fee' => 2000]);
        $line = $order->lineItems()->create([
            'product_name' => 'School Bag', 'sku' => 'BAG-01', 'quantity' => 0,
            'ordered_quantity' => 2, 'unavailable_quantity' => 2, 'unavailable_reason' => 'damaged',
            'unit_cost' => 15000, 'unit_price' => 25000, 'line_total' => 0,
            'cost_total' => 0, 'profit_total' => 0,
        ]);
        $order->update(['packed_at' => now(), 'packed_by' => $admin->id]);

        $this->assertSame(52000, $order->fresh()->manualRefundDue());
        $this->actingAs($admin)->patch(route('admin.invoices.manual-refund', $order), [
            'manual_refund_reference' => 'DRY-RUN-REFUND-01',
        ])->assertRedirect();

        $this->assertSame(ShoppingList::PAYMENT_REFUNDED, $order->fresh()->payment_status);
        $this->assertSame(ShoppingList::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(52000, $order->fresh()->manual_refund_amount);
        $this->assertSame('refunded', $order->fresh()->deliveryStatus());
    }

    public function test_stock_exception_can_be_resolved_by_packing_only_the_available_quantity(): void
    {
        Mail::fake();
        [$admin, $order] = $this->paidOrder([
            'status' => ShoppingList::STATUS_REVIEWING,
            'payment_exception' => 'Only one copy remains in display stock.',
            'payment_exception_type' => 'stock',
        ]);
        $driver = Driver::create(['name' => 'Amina Driver', 'is_approved' => true, 'is_available' => true]);
        $product = Product::create([
            'name' => 'Exercise Book', 'slug' => 'stock-exception-book', 'sku' => 'STOCK-EX-01',
            'price' => 5000, 'cost_price' => 3000, 'is_active' => true,
        ]);
        app(InventoryService::class)->recordOpeningStock($product, [
            'warehouse_quantity' => 0, 'display_quantity' => 1, 'unit_cost' => 3000,
            'unit_price' => 5000, 'occurred_at' => now()->toDateString(),
        ]);
        $line = $order->lineItems()->create([
            'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
            'quantity' => 2, 'unit_cost' => 3000, 'unit_price' => 5000,
            'line_total' => 10000, 'cost_total' => 0, 'profit_total' => 0,
        ]);

        $this->actingAs($admin)->patch(route('admin.invoices.fulfilment', $order), [
            'items' => [$line->id => ['unavailable_quantity' => 1, 'unavailable_reason' => 'out_of_stock']],
            'packed_items_confirmed' => true,
            'assigned_driver_id' => $driver->id,
        ])->assertRedirect();

        $this->assertNull($order->fresh()->payment_exception);
        $this->assertSame(ShoppingList::STATUS_QUOTED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->packed_at);
        $this->assertSame(1, $order->fresh()->lineItems->first()->quantity);
        $this->assertSame(0, $product->fresh()->stock_quantity);
        $this->assertSame(2, $order->fresh()->lineItems->first()->ordered_quantity);

        $this->actingAs($admin)->patch(route('admin.invoices.fulfilment', $order), [
            'items' => [$line->id => ['unavailable_quantity' => 0]],
            'packed_items_confirmed' => true,
            'assigned_driver_id' => $driver->id,
        ])->assertSessionHasErrors("items.{$line->id}.unavailable_quantity");

        $this->actingAs($admin)->patch(route('admin.invoices.fulfilment', $order), [
            'items' => [$line->id => ['unavailable_quantity' => 0]],
            'packed_items_confirmed' => true,
            'assigned_driver_id' => $driver->id,
        ])->assertSessionHasErrors("items.{$line->id}.unavailable_quantity");
    }

    public function test_shortage_splits_delivered_sale_from_stock_writeoff_without_deducting_stock_twice(): void
    {
        Mail::fake();
        config(['edukit.payment_mode' => 'demo', 'app.env' => 'testing']);
        [$admin, $order] = $this->paidOrder([
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
            'payment_provider' => null,
        ]);
        $product = Product::create([
            'name' => 'Exercise Book', 'slug' => 'shortage-exercise-book', 'sku' => 'SHORT-BOOK-01',
            'price' => 5000, 'cost_price' => 3000, 'is_active' => true,
        ]);
        app(InventoryService::class)->recordOpeningStock($product, [
            'warehouse_quantity' => 0,
            'display_quantity' => 2,
            'unit_cost' => 3000,
            'unit_price' => 5000,
            'occurred_at' => now()->toDateString(),
        ]);
        $line = $order->lineItems()->create([
            'product_id' => $product->id,
            'fulfilment_source' => 'edukit',
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_cost' => 3000,
            'unit_price' => 5000,
            'line_total' => 10000,
        ]);

        app(DemoPaymentService::class)->pay($order);
        $this->assertSame(0, $product->fresh()->stock_quantity);

        $driver = Driver::create(['name' => 'Amina Driver', 'is_approved' => true, 'is_available' => true]);
        $this->actingAs($admin)->patch(route('admin.invoices.fulfilment', $order), [
            'items' => [$line->id => ['unavailable_quantity' => 1, 'unavailable_reason' => 'damaged']],
            'packed_items_confirmed' => true,
            'assigned_driver_id' => $driver->id,
        ])->assertRedirect();

        $firstPackedAt = $order->fresh()->packed_at;
        $this->travel(1)->minutes();
        $this->actingAs($admin)->patch(route('admin.invoices.fulfilment', $order), [
            'items' => [$line->id => ['unavailable_quantity' => 1, 'unavailable_reason' => 'damaged']],
            'packed_items_confirmed' => true,
            'assigned_driver_id' => $driver->id,
        ])->assertRedirect();
        $this->assertEquals($firstPackedAt, $order->fresh()->packed_at);
        $this->assertSame(1, InventoryMovement::where('shopping_list_id', $order->id)->where('type', InventoryMovement::TYPE_SALE_PAID)->count());
        $this->assertSame(1, InventoryMovement::where('shopping_list_id', $order->id)->where('type', InventoryMovement::TYPE_UNAVAILABLE_STOCK_WRITEOFF)->count());

        $sale = InventoryMovement::where('shopping_list_id', $order->id)
            ->where('type', InventoryMovement::TYPE_SALE_PAID)->firstOrFail();
        $writeoff = InventoryMovement::where('shopping_list_id', $order->id)
            ->where('type', InventoryMovement::TYPE_UNAVAILABLE_STOCK_WRITEOFF)->firstOrFail();

        $this->assertSame(1, $sale->quantity);
        $this->assertSame(-1, $sale->display_quantity_delta);
        $this->assertSame(3000.0, (float) $sale->total_cost);
        $this->assertSame(5000.0, (float) $sale->total_revenue);
        $this->assertSame(2000.0, (float) $sale->profit);
        $this->assertSame(1, $writeoff->quantity);
        $this->assertSame(-1, $writeoff->display_quantity_delta);
        $this->assertSame(3000.0, (float) $writeoff->total_cost);
        $this->assertSame(-3000.0, (float) $writeoff->profit);
        $this->assertSame(-1000.0, (float) $order->fresh()->lineItems->first()->profit_total);
        $this->assertSame(0, $product->fresh()->stock_quantity);

        $dates = ['from' => today()->toDateString(), 'to' => today()->toDateString()];
        $this->actingAs($admin)
            ->get(route('admin.inventory.sales', $dates + ['include_demo' => 1]))
            ->assertOk()
            ->assertSee('Unavailable stock write-off')
            ->assertViewHas('stats', fn ($stats): bool => $stats['units'] === 1
                && (int) $stats['revenue'] === 5000
                && (int) $stats['writeoff_cost'] === 3000
                && (int) $stats['profit'] === -1000);

        $this->get(route('admin.inventory.index', $dates + ['include_demo' => 1]))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats): bool => $stats['sold_units'] === 1
                && (int) $stats['gross_profit'] === -1000);

        $this->patch(route('admin.invoices.fulfilment', $order), [
            'items' => [$line->id => ['unavailable_quantity' => 0]],
            'packed_items_confirmed' => true,
            'assigned_driver_id' => $driver->id,
        ])->assertRedirect();
        $this->assertDatabaseMissing('inventory_movements', [
            'shopping_list_id' => $order->id,
            'type' => InventoryMovement::TYPE_UNAVAILABLE_STOCK_WRITEOFF,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'shopping_list_id' => $order->id,
            'type' => InventoryMovement::TYPE_SALE_PAID,
            'quantity' => 2,
            'display_quantity_delta' => -2,
        ]);
        $this->assertSame(0, $product->fresh()->stock_quantity);

        $this->patch(route('admin.invoices.fulfilment', $order), [
            'items' => [$line->id => ['unavailable_quantity' => 0]],
            'packed_items_confirmed' => true,
            'assigned_driver_id' => $driver->id,
        ])->assertRedirect();
        $this->assertDatabaseMissing('inventory_movements', [
            'shopping_list_id' => $order->id,
            'type' => InventoryMovement::TYPE_UNAVAILABLE_STOCK_WRITEOFF,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'shopping_list_id' => $order->id,
            'type' => InventoryMovement::TYPE_SALE_PAID,
            'quantity' => 2,
            'display_quantity_delta' => -2,
        ]);
        $this->assertSame(0, $product->fresh()->stock_quantity);
    }

    public function test_driver_cannot_start_a_paid_order_until_admin_marks_it_packed(): void
    {
        PermissionRole::findOrCreate('delivery-person', 'web');
        $driverUser = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $driverUser->assignRole('delivery-person');
        $driver = Driver::create(['user_id' => $driverUser->id, 'name' => 'Amina Driver', 'is_approved' => true, 'is_available' => true]);
        $order = ShoppingList::create([
            'parent_name' => 'Customer', 'phone' => '+256700000000', 'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID, 'assigned_driver_id' => $driver->id,
        ]);
        $order->lineItems()->create(['product_name' => 'Pen', 'quantity' => 1, 'unit_price' => 1000, 'line_total' => 1000]);

        $this->actingAs($driverUser)->patch(route('driver.deliveries.start', $order))
            ->assertSessionHasErrors('delivery');
        $this->assertNull($order->fresh()->driver_started_at);
    }

    /** @return array{0:User, 1:ShoppingList} */
    private function paidOrder(array $overrides = []): array
    {
        PermissionRole::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $admin->assignRole('super-admin');
        $order = ShoppingList::create(array_merge([
            'parent_name' => 'Norah Customer', 'phone' => '+256700000000', 'email' => 'norah@example.test',
            'source' => ShoppingList::SOURCE_CART, 'items_subtotal' => 10000, 'delivery_fee' => 1000,
            'estimated_total' => 11000, 'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID, 'payment_provider' => 'demo', 'paid_at' => now(),
        ], $overrides));

        return [$admin, $order];
    }
}
