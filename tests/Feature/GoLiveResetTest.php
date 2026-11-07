<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\DriverPayout;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoLiveResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_page_is_super_admin_only_and_disabled_by_default(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)
            ->get(route('admin.inventory.go-live-reset'))
            ->assertForbidden();

        $superAdmin = $this->userWithRole('super-admin');
        $this->actingAs($superAdmin)
            ->get(route('admin.inventory.go-live-reset'))
            ->assertOk()
            ->assertSee('Reset is safely disabled')
            ->assertDontSee('Reset pre-launch data');
    }

    public function test_reset_requires_feature_flag_password_and_explicit_confirmation(): void
    {
        $admin = $this->userWithRole('super-admin');
        $this->actingAs($admin)
            ->post(route('admin.inventory.go-live-reset.store'), $this->confirmationData())
            ->assertNotFound();

        config(['edukit.go_live_reset_enabled' => true]);
        $product = $this->productWithStock();

        $this->post(route('admin.inventory.go-live-reset.store'), [
            ...$this->confirmationData(),
            'current_password' => 'wrong-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 4, 'price' => 5000]);
        $this->assertDatabaseCount('go_live_reset_runs', 0);
    }

    public function test_one_time_reset_clears_prelaunch_transactions_and_stock_but_retains_catalogue(): void
    {
        Storage::fake('local');
        config(['filesystems.documents_disk' => 'local']);
        config(['edukit.go_live_reset_enabled' => true]);

        $admin = $this->userWithRole('super-admin');
        $product = $this->productWithStock();
        $order = ShoppingList::create([
            'parent_name' => 'Test Customer',
            'phone' => '+256700000001',
            'email' => 'test@example.test',
            'source' => ShoppingList::SOURCE_CART,
            'reference' => 'EDK-TEST-RESET-001',
            'file_path' => 'shopping-lists/test-order.pdf',
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'demo',
        ]);
        ShoppingList::create([
            'parent_name' => 'Unpaid Test Customer',
            'phone' => '+256700000003',
            'source' => ShoppingList::SOURCE_CART,
            'reference' => 'EDK-TEST-RESET-002',
            'status' => ShoppingList::STATUS_PENDING,
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
            'payment_provider' => null,
        ]);
        Storage::disk('local')->put($order->file_path, 'test upload');
        PaymentAttempt::create([
            'shopping_list_id' => $order->id,
            'tx_ref' => 'TEST-RESET-TX-1',
            'amount' => 5000,
            'currency' => 'UGX',
            'status' => 'demo_paid',
        ]);
        $driver = Driver::create(['name' => 'Test Driver']);
        DriverPayout::create([
            'shopping_list_id' => $order->id,
            'driver_id' => $driver->id,
            'amount' => 1000,
            'payment_phone' => '+256700000002',
            'transaction_reference' => 'TEST-PAYOUT-1',
            'paid_at' => now(),
        ]);
        DB::table('notifications')->insert([
            'id' => '2a4c3f9a-c56f-4ba6-a28a-2f05ddbd7fd3',
            'type' => 'App\\Notifications\\OrderWorkflowNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'data' => json_encode(['reference' => $order->reference, 'title' => 'Test order']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.inventory.go-live-reset'))
            ->post(route('admin.inventory.go-live-reset.store'), $this->confirmationData())
            ->assertRedirect(route('admin.inventory.go-live-reset'))
            ->assertSessionHas('status')
            ->assertSessionHas('reset_summary.orders_removed', 2);

        $this->assertDatabaseCount('shopping_lists', 0);
        $this->assertDatabaseCount('shopping_list_items', 0);
        $this->assertDatabaseCount('payment_attempts', 0);
        $this->assertDatabaseCount('driver_payouts', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('inventory_batches', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('product_categories', 1);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'product_category_id' => $product->product_category_id,
            'name' => 'Exercise Book',
            'sku' => 'EDK-RESET-001',
            'description' => 'Catalogue description to retain',
            'color' => 'Blue',
            'image_path' => 'products/exercise-book.jpg',
            'warehouse_stock_quantity' => 0,
            'stock_quantity' => 0,
            'cost_price' => 0,
            'price' => 0,
            'is_active' => 0,
        ]);
        Storage::disk('local')->assertMissing('shopping-lists/test-order.pdf');
        $this->assertDatabaseCount('go_live_reset_runs', 1);

        $this->get(route('admin.inventory.go-live-reset'))
            ->assertOk()
            ->assertSee('This one-time reset has already been completed');
    }

    public function test_reset_cannot_be_run_a_second_time(): void
    {
        config(['edukit.go_live_reset_enabled' => true]);
        $admin = $this->userWithRole('super-admin');
        $this->actingAs($admin)->post(route('admin.inventory.go-live-reset.store'), $this->confirmationData());

        $product = $this->productWithStock();
        $this->post(route('admin.inventory.go-live-reset.store'), $this->confirmationData())
            ->assertSessionHasErrors('reset');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'warehouse_stock_quantity' => 3, 'stock_quantity' => 4]);
    }

    private function productWithStock(): Product
    {
        $category = ProductCategory::create([
            'name' => 'Books',
            'slug' => 'books-reset-test',
            'description' => 'Keep this category',
        ]);
        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Exercise Book',
            'slug' => 'exercise-book-reset-test',
            'sku' => 'EDK-RESET-001',
            'description' => 'Catalogue description to retain',
            'color' => 'Blue',
            'image_path' => 'products/exercise-book.jpg',
            'cost_price' => 2500,
            'price' => 5000,
            'warehouse_stock_quantity' => 3,
            'stock_quantity' => 4,
            'reorder_level' => 5,
            'is_active' => true,
        ]);
        InventoryBatch::create([
            'product_id' => $product->id,
            'batch_reference' => 'RESET-BATCH-001',
            'source' => InventoryBatch::SOURCE_OPENING_STOCK,
            'received_at' => today(),
            'quantity_received' => 7,
            'remaining_quantity' => 7,
            'warehouse_remaining_quantity' => 3,
            'display_remaining_quantity' => 4,
            'unit_cost' => 2500,
            'unit_price' => 5000,
        ]);
        InventoryMovement::create([
            'product_id' => $product->id,
            'inventory_batch_id' => $product->inventoryBatches()->first()->id,
            'type' => InventoryMovement::TYPE_OPENING_STOCK,
            'quantity' => 7,
            'warehouse_quantity_delta' => 3,
            'display_quantity_delta' => 4,
            'unit_cost' => 2500,
            'unit_price' => 5000,
            'total_cost' => 17500,
            'total_revenue' => 0,
            'profit' => 0,
            'occurred_at' => now(),
        ]);

        return $product;
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function confirmationData(): array
    {
        return [
            'confirm_all_orders_are_test' => '1',
            'confirmation_phrase' => 'RESET PRE-LAUNCH TEST DATA',
            'current_password' => 'password',
        ];
    }
}
