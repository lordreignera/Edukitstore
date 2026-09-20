<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ShoppingList;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_supplier_can_view_its_dashboard_and_stock_only(): void
    {
        Role::findOrCreate('supplier');
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->assignRole('supplier');
        $supplier = Supplier::create([
            'user_id' => $user->id,
            'business_name' => 'Kikuubo Stationers',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $otherSupplier = Supplier::create(['business_name' => 'Other Supplier', 'is_approved' => true, 'is_active' => true]);
        $category = ProductCategory::create(['name' => 'Books', 'slug' => 'books', 'is_active' => true]);
        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Exercise Book',
            'slug' => 'exercise-book',
            'sku' => 'EDK-BK-0001',
            'price' => 1800,
            'cost_price' => 1600,
            'is_active' => true,
        ]);
        InventoryBatch::create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'batch_reference' => 'BATCH-001',
            'received_at' => today(),
            'quantity_received' => 100,
            'remaining_quantity' => 80,
            'warehouse_remaining_quantity' => 80,
            'unit_cost' => 1600,
            'unit_price' => 1800,
        ]);
        InventoryBatch::create([
            'product_id' => $product->id,
            'supplier_id' => $otherSupplier->id,
            'batch_reference' => 'PRIVATE-BATCH',
            'received_at' => today(),
            'quantity_received' => 10,
            'remaining_quantity' => 10,
            'warehouse_remaining_quantity' => 10,
            'unit_cost' => 1500,
            'unit_price' => 1800,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('supplier.dashboard'));
        $this->actingAs($user)->get(route('supplier.dashboard'))->assertOk()->assertSee('Kikuubo Stationers')->assertSee('BATCH-001')->assertDontSee('PRIVATE-BATCH');
        $this->actingAs($user)->get(route('supplier.stock.index'))->assertOk()->assertSee('BATCH-001')->assertDontSee('PRIVATE-BATCH');
    }

    public function test_approved_driver_can_view_only_assigned_deliveries(): void
    {
        Role::findOrCreate('delivery-person');
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->assignRole('delivery-person');
        $driver = Driver::create([
            'user_id' => $user->id,
            'name' => 'John Driver',
            'is_approved' => true,
            'is_available' => true,
        ]);
        $otherDriver = Driver::create(['name' => 'Other Driver', 'is_approved' => true, 'is_available' => true]);
        ShoppingList::create([
            'parent_name' => 'Sarah Parent',
            'phone' => '0700000001',
            'reference' => 'EDK-260919-1000',
            'assigned_driver_id' => $driver->id,
            'payment_status' => ShoppingList::PAYMENT_PAID,
        ]);
        ShoppingList::create([
            'parent_name' => 'Private Customer',
            'phone' => '0700000002',
            'reference' => 'EDK-260919-1001',
            'assigned_driver_id' => $otherDriver->id,
            'payment_status' => ShoppingList::PAYMENT_PAID,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('driver.dashboard'));
        $this->actingAs($user)->get(route('driver.dashboard'))->assertOk()->assertSee('EDK-260919-1000')->assertDontSee('EDK-260919-1001');
        $this->actingAs($user)->get(route('driver.deliveries.index'))->assertOk()->assertSee('EDK-260919-1000')->assertDontSee('EDK-260919-1001');

        $this->actingAs($user)->patch(route('driver.availability'), [
            'is_available' => '0',
            'availability_note' => 'Vehicle service',
        ])->assertRedirect();

        $driver->refresh();
        $this->assertFalse($driver->is_available);
        $this->assertSame('Vehicle service', $driver->availability_note);
        $this->assertNotNull($driver->availability_updated_at);
        $this->actingAs($user)->get(route('driver.dashboard'))->assertOk()->assertSee('Unavailable for new trips')->assertSee('EDK-260919-1000');
    }
}
