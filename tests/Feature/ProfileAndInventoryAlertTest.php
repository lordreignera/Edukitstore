<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\User;
use App\Notifications\InventoryThresholdReachedNotification;
use App\Services\InventoryService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileAndInventoryAlertTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionSeeder::class)->run();
    }

    public function test_admin_profile_uses_the_admin_workspace_shell(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('super-admin', 'web'));

        $this->actingAs($admin)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Commerce')
            ->assertSee('Partners & Schools')
            ->assertSee('Monitoring & Analytics')
            ->assertSee('Profile settings')
            ->assertSee(route('admin.dashboard'));
    }

    public function test_paid_sales_notify_admins_once_when_display_stock_crosses_the_reorder_level(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('super-admin', 'web'));

        $product = Product::create([
            'name' => 'Threshold Test Exercise Book',
            'slug' => 'threshold-test-exercise-book',
            'sku' => 'EDK-THRESHOLD-1',
            'cost_price' => 1000,
            'price' => 2000,
            'reorder_level' => 2,
            'is_active' => true,
        ]);

        $inventory = app(InventoryService::class);
        $inventory->recordOpeningStock($product, [
            'warehouse_quantity' => 0,
            'display_quantity' => 5,
            'unit_cost' => 1000,
            'unit_price' => 2000,
            'occurred_at' => now()->toDateString(),
        ]);

        $inventory->recordPaidCartSale($this->cartOrder($product, 3));

        Notification::assertSentTo($admin, InventoryThresholdReachedNotification::class, function (InventoryThresholdReachedNotification $notification): bool {
            $data = $notification->toArray(new User);

            return $data['stock_quantity'] === 2 && $data['reorder_level'] === 2;
        });

        $inventory->recordPaidCartSale($this->cartOrder($product->fresh(), 1));

        $this->assertCount(1, Notification::sent($admin, InventoryThresholdReachedNotification::class));
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_inventory_table_marks_available_stock_green_and_low_stock_red(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('super-admin', 'web'));

        Product::create([
            'name' => 'Healthy Stock Book',
            'slug' => 'healthy-stock-book',
            'sku' => 'EDK-HEALTHY-1',
            'price' => 2000,
            'cost_price' => 1000,
            'stock_quantity' => 8,
            'reorder_level' => 3,
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Low Stock Book',
            'slug' => 'low-stock-book',
            'sku' => 'EDK-LOW-1',
            'price' => 2000,
            'cost_price' => 1000,
            'stock_quantity' => 2,
            'reorder_level' => 3,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('In stock')
            ->assertSee('Low stock')
            ->assertSee('text-emerald-700')
            ->assertSee('text-red-700');
    }

    private function cartOrder(Product $product, int $quantity): ShoppingList
    {
        return ShoppingList::create([
            'parent_name' => 'Inventory Alert Customer',
            'phone' => '+256700123456',
            'delivery_preference' => 'school',
            'source' => ShoppingList::SOURCE_CART,
            'cart_items' => [[
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => 2000,
                'quantity' => $quantity,
                'line_total' => $quantity * 2000,
            ]],
            'items_subtotal' => $quantity * 2000,
            'delivery_fee' => 0,
            'estimated_total' => $quantity * 2000,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PENDING,
        ]);
    }
}
