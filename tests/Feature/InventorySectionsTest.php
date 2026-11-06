<?php

namespace Tests\Feature;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventorySectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_subpages_show_warehouse_display_and_paid_sales_data(): void
    {
        $admin = $this->superAdmin();
        $product = Product::create([
            'name' => 'Exercise Book',
            'slug' => 'exercise-book',
            'sku' => 'EDK-INV-001',
            'cost_price' => 3000,
            'price' => 5000,
            'warehouse_stock_quantity' => 14,
            'stock_quantity' => 8,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        InventoryBatch::create([
            'product_id' => $product->id,
            'batch_reference' => 'BATCH-INV-001',
            'source' => InventoryBatch::SOURCE_OPENING_STOCK,
            'received_at' => today(),
            'quantity_received' => 12,
            'remaining_quantity' => 12,
            'warehouse_remaining_quantity' => 8,
            'display_remaining_quantity' => 4,
            'unit_cost' => 2000,
            'unit_price' => 5000,
        ]);
        Product::create([
            'name' => 'Archived Workbook',
            'slug' => 'archived-workbook-inventory-test',
            'sku' => 'EDK-INV-003',
            'price' => 7000,
            'stock_quantity' => 2,
            'is_active' => false,
        ]);
        InventoryBatch::create([
            'product_id' => $product->id,
            'batch_reference' => 'BATCH-INV-002',
            'source' => InventoryBatch::SOURCE_STOCK_INTAKE,
            'received_at' => today(),
            'quantity_received' => 10,
            'remaining_quantity' => 10,
            'warehouse_remaining_quantity' => 6,
            'display_remaining_quantity' => 4,
            'unit_cost' => 4000,
            'unit_price' => 5000,
        ]);

        $order = ShoppingList::create([
            'parent_name' => 'Test Customer',
            'phone' => '+256700000001',
            'source' => ShoppingList::SOURCE_CART,
            'cart_items' => [[
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => 5000,
                'quantity' => 3,
            ]],
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'manual',
            'estimated_total' => 15000,
            'paid_at' => now(),
        ]);
        app(InventoryService::class)->recordPaidCartSale($order);

        $this->actingAs($admin)
            ->get(route('admin.inventory.warehouse'))
            ->assertOk()
            ->assertSee('Warehouse Stock')
            ->assertSee('Warehouse units')
            ->assertSee('14')
            ->assertSee('UGX 2,857');

        $this->get(route('admin.inventory.display'))
            ->assertOk()
            ->assertSee('Display Stock')
            ->assertSee('Product lines at/below reorder')
            ->assertSee('Exercise Book')
            ->assertSee('Hidden listing')
            ->assertSee('Ledger mismatch: product count 2, batch quantity 0');

        $this->get(route('admin.inventory.sales', [
            'from' => today()->toDateString(),
            'to' => today()->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('Sales History')
            ->assertSee($order->reference)
            ->assertSee('UGX 15,000')
            ->assertSee('UGX 9,000')
            ->assertSee('View details')
            ->assertSee('Sale totals')
            ->assertSee('Current catalogue details')
            ->assertViewHas('stats', fn ($stats) => $stats['units'] === 3
                && (int) $stats['revenue'] === 15000
                && (int) $stats['profit'] === 9000
                && $stats['orders'] === 1)
            ->assertDontSee('Includes demo/test payments');
    }

    public function test_inventory_financial_totals_exclude_demo_orders_until_included(): void
    {
        $admin = $this->superAdmin();
        $product = Product::create([
            'name' => 'Graph Book',
            'slug' => 'graph-book-inventory-test',
            'sku' => 'EDK-INV-002',
            'cost_price' => 3000,
            'price' => 5000,
            'warehouse_stock_quantity' => 0,
            'stock_quantity' => 10,
            'reorder_level' => 1,
            'is_active' => true,
        ]);
        InventoryBatch::create([
            'product_id' => $product->id,
            'batch_reference' => 'BATCH-INV-003',
            'source' => InventoryBatch::SOURCE_OPENING_STOCK,
            'received_at' => today(),
            'quantity_received' => 10,
            'remaining_quantity' => 10,
            'warehouse_remaining_quantity' => 0,
            'display_remaining_quantity' => 10,
            'unit_cost' => 3000,
            'unit_price' => 5000,
        ]);

        $realOrder = $this->recordPaidOrder($product, 'manual', 1);
        $demoOrder = $this->recordPaidOrder($product, 'demo', 1);
        $dates = ['from' => today()->toDateString(), 'to' => today()->toDateString()];

        $this->actingAs($admin)
            ->get(route('admin.inventory.sales', $dates))
            ->assertOk()
            ->assertSee($realOrder->reference)
            ->assertDontSee($demoOrder->reference)
            ->assertSee('UGX 5,000')
            ->assertSee('UGX 2,000')
            ->assertViewHas('stats', fn ($stats) => $stats['units'] === 1
                && (int) $stats['revenue'] === 5000
                && (int) $stats['profit'] === 2000
                && $stats['orders'] === 1);

        $this->get(route('admin.inventory.sales', $dates + ['include_demo' => 1]))
            ->assertOk()
            ->assertSee($realOrder->reference)
            ->assertSee($demoOrder->reference)
            ->assertSee('UGX 10,000')
            ->assertSee('UGX 4,000')
            ->assertSee('Includes demo/test payments')
            ->assertViewHas('stats', fn ($stats) => $stats['units'] === 2
                && (int) $stats['revenue'] === 10000
                && (int) $stats['profit'] === 4000
                && $stats['orders'] === 2);

        $this->get(route('admin.inventory.index', $dates))
            ->assertOk()
            ->assertSee($realOrder->reference)
            ->assertDontSee($demoOrder->reference)
            ->assertSee('Include demo payments')
            ->assertSee('UGX 2,000')
            ->assertViewHas('stats', fn ($stats) => $stats['sold_units'] === 1
                && (int) $stats['gross_profit'] === 2000);

        $this->get(route('admin.inventory.index', $dates + ['include_demo' => 1]))
            ->assertOk()
            ->assertSee($demoOrder->reference)
            ->assertSee('Test payment')
            ->assertSee('UGX 4,000')
            ->assertViewHas('stats', fn ($stats) => $stats['sold_units'] === 2
                && (int) $stats['gross_profit'] === 4000);
    }

    public function test_inventory_dropdown_links_to_each_stock_review_screen(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.inventory.display'))
            ->assertOk()
            ->assertSee('Stock Overview')
            ->assertSee('Warehouse Stock')
            ->assertSee('Display Stock')
            ->assertSee('Sales History')
            ->assertSee(route('admin.inventory.index'))
            ->assertSee(route('admin.inventory.warehouse'))
            ->assertSee(route('admin.inventory.display'))
            ->assertSee(route('admin.inventory.sales'));
    }

    private function superAdmin(): User
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }

    private function recordPaidOrder(Product $product, string $provider, int $quantity): ShoppingList
    {
        $order = ShoppingList::create([
            'parent_name' => 'Inventory Test Customer',
            'phone' => '+256700000002',
            'source' => ShoppingList::SOURCE_CART,
            'cart_items' => [[
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => 5000,
                'quantity' => $quantity,
            ]],
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => $provider,
            'estimated_total' => 5000 * $quantity,
            'paid_at' => now(),
        ]);

        app(InventoryService::class)->recordPaidCartSale($order);

        return $order;
    }
}
