<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\School;
use App\Models\User;
use Database\Seeders\EduKitProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_product_for_public_catalogue(): void
    {
        Storage::fake('public');
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $category = ProductCategory::create([
            'name' => 'Books',
            'slug' => 'books',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'product_category_id' => $category->id,
            'name' => 'P.7 Integrated Science Revision Book',
            'sku' => 'MANUAL-CODE-SHOULD-BE-IGNORED',
            'description' => 'Revision book for Ugandan primary candidates.',
            'brand' => 'EduKit Books',
            'unit' => 'Book',
            'cost_price' => 22000,
            'price' => 28000,
            'reorder_level' => 8,
            'opening_stock_date' => now()->toDateString(),
            'opening_warehouse_quantity' => 40,
            'opening_display_quantity' => 25,
            'image' => UploadedFile::fake()->image('science-revision.jpg', 900, 700),
            'is_active' => '1',
            'is_featured' => '1',
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'P.7 Integrated Science Revision Book')->firstOrFail();

        $this->assertMatchesRegularExpression('/^EDK\d{9}$/', $product->sku);
        $this->assertNotSame('MANUAL-CODE-SHOULD-BE-IGNORED', $product->sku);
        $this->assertSame(28000, (int) $product->price);
        $this->assertSame(40, $product->warehouse_stock_quantity);
        $this->assertSame(25, $product->stock_quantity);
        $this->assertTrue($product->is_active);
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertDatabaseHas('inventory_batches', [
            'product_id' => $product->id,
            'source' => InventoryBatch::SOURCE_OPENING_STOCK,
            'quantity_received' => 65,
            'remaining_quantity' => 65,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => InventoryMovement::TYPE_OPENING_STOCK,
            'quantity' => 65,
        ]);

        $this->get(route('website.products.index'))
            ->assertOk()
            ->assertSee('P.7 Integrated Science Revision Book')
            ->assertSee('UGX 28,000');
    }

    public function test_admin_product_index_links_to_view_edit_and_delete_actions(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $product = Product::create([
            'name' => 'Oxford Mathematical Set',
            'slug' => 'oxford-mathematical-set',
            'sku' => 'EDK260900001',
            'price' => 18000,
            'stock_quantity' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee(route('admin.products.show', $product), false)
            ->assertSee(route('admin.products.edit', $product), false)
            ->assertSee(route('admin.products.destroy', $product), false);

        $this->actingAs($admin)
            ->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertSee('Oxford Mathematical Set')
            ->assertSee('EDK260900001');

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['sku' => 'EDK260900001']);
    }

    public function test_admin_can_replace_product_image_without_changing_generated_code(): void
    {
        Storage::fake('public');
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        Storage::disk('public')->put('products/old-cover.jpg', 'old image');

        $product = Product::create([
            'name' => 'Counter Book',
            'slug' => 'counter-book',
            'sku' => 'EDK260900003',
            'price' => 5000,
            'stock_quantity' => 80,
            'image_path' => 'products/old-cover.jpg',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'name' => 'Counter Book A4',
                'sku' => 'DO-NOT-CHANGE-ME',
                'cost_price' => 4500,
                'price' => 6000,
                'reorder_level' => 10,
                'image' => UploadedFile::fake()->image('new-cover.png', 800, 800),
                'is_active' => '1',
            ])->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('EDK260900003', $product->sku);
        $this->assertSame('Counter Book A4', $product->name);
        $this->assertSame(80, $product->stock_quantity);
        $this->assertNotSame('products/old-cover.jpg', $product->image_path);
        Storage::disk('public')->assertMissing('products/old-cover.jpg');
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_super_admin_can_manage_product_categories(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->post(route('admin.product-categories.store'), [
                'name' => 'Textbooks',
                'description' => 'Curriculum books and readers.',
                'is_active' => '1',
            ])->assertRedirect(route('admin.product-categories.index'));

        $category = ProductCategory::where('name', 'Textbooks')->firstOrFail();
        $this->assertSame('textbooks', $category->slug);
        $this->assertTrue($category->is_active);

        $this->actingAs($admin)
            ->get(route('admin.product-categories.index', ['q' => 'Textbooks']))
            ->assertOk()
            ->assertSee('Textbooks')
            ->assertSee(route('admin.product-categories.show', $category), false)
            ->assertSee(route('admin.product-categories.edit', $category), false)
            ->assertSee(route('admin.product-categories.destroy', $category), false);

        $this->actingAs($admin)
            ->put(route('admin.product-categories.update', $category), [
                'name' => 'Revision Books',
                'description' => 'PLE and lower secondary revision materials.',
                'is_active' => '0',
            ])->assertRedirect(route('admin.product-categories.index'));

        $category->refresh();
        $this->assertSame('Revision Books', $category->name);
        $this->assertSame('revision-books', $category->slug);
        $this->assertFalse($category->is_active);

        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'PLE Science Revision Book',
            'slug' => 'ple-science-revision-book',
            'sku' => 'EDK260900002',
            'price' => 25000,
            'stock_quantity' => 12,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.product-categories.show', $category))
            ->assertOk()
            ->assertSee('PLE Science Revision Book');

        $this->actingAs($admin)
            ->delete(route('admin.product-categories.destroy', $category))
            ->assertRedirect(route('admin.product-categories.index'));

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
        $this->assertNull($product->fresh()->product_category_id);
    }

    public function test_admin_can_record_stock_intake_and_move_stock_to_display(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $product = Product::create([
            'name' => 'Exercise Book Dozen',
            'slug' => 'exercise-book-dozen',
            'sku' => 'EDK260900888',
            'cost_price' => 1600,
            'price' => 1800,
            'warehouse_stock_quantity' => 0,
            'stock_quantity' => 2,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inventory.intake', $product), [
                'occurred_at' => now()->toDateString(),
                'quantity' => 24,
                'unit_cost' => 1600,
                'unit_price' => 1800,
                'notes' => 'Opening Kikuubo stock.',
            ])
            ->assertRedirect();

        $product->refresh();

        $this->assertSame(24, $product->warehouse_stock_quantity);
        $this->assertSame(2, $product->stock_quantity);
        $this->assertSame(1800, (int) $product->price);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => InventoryMovement::TYPE_STOCK_INTAKE,
            'quantity' => 24,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inventory.transfer', $product), [
                'occurred_at' => now()->toDateString(),
                'quantity' => 10,
                'notes' => 'Move to website shelf.',
            ])
            ->assertRedirect();

        $product->refresh();

        $this->assertSame(14, $product->warehouse_stock_quantity);
        $this->assertSame(12, $product->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => InventoryMovement::TYPE_TRANSFER_TO_DISPLAY,
            'quantity' => 10,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('Exercise Book Dozen')
            ->assertSee('UGX 200');
    }

    public function test_admin_can_edit_and_delete_unused_inventory_movements(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $product = Product::create([
            'name' => 'Dustless Chalk Box',
            'slug' => 'dustless-chalk-box',
            'sku' => 'EDK260900889',
            'cost_price' => 3000,
            'price' => 4500,
            'reorder_level' => 3,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inventory.opening', $product), [
                'occurred_at' => now()->subDay()->toDateString(),
                'warehouse_quantity' => 10,
                'display_quantity' => 5,
                'unit_cost' => 3000,
                'unit_price' => 4500,
                'notes' => 'Launch count.',
            ])
            ->assertRedirect();

        $movement = InventoryMovement::where('type', InventoryMovement::TYPE_OPENING_STOCK)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.inventory.movements.update', $movement), [
                'occurred_at' => now()->toDateString(),
                'warehouse_quantity' => 8,
                'display_quantity' => 4,
                'unit_cost' => 3200,
                'unit_price' => 5000,
                'notes' => 'Corrected physical count.',
            ])
            ->assertRedirect();

        $product->refresh();
        $movement->refresh();

        $this->assertSame(8, $product->warehouse_stock_quantity);
        $this->assertSame(4, $product->stock_quantity);
        $this->assertSame(12, $movement->quantity);
        $this->assertSame(12, $movement->inventoryBatch->quantity_received);
        $this->assertSame(12, $movement->inventoryBatch->remaining_quantity);
        $this->assertSame(5000, (int) $product->price);

        $this->actingAs($admin)
            ->delete(route('admin.inventory.movements.destroy', $movement))
            ->assertRedirect();

        $product->refresh();

        $this->assertSame(0, $product->warehouse_stock_quantity);
        $this->assertSame(0, $product->stock_quantity);
        $this->assertDatabaseMissing('inventory_movements', ['id' => $movement->id]);
        $this->assertDatabaseMissing('inventory_batches', ['id' => $movement->inventory_batch_id]);
    }

    public function test_inventory_transfers_and_sales_follow_display_batch_costing(): void
    {
        $product = Product::create([
            'name' => 'Exercise Book Dozen',
            'slug' => 'exercise-book-dozen',
            'sku' => 'EDK260900890',
            'cost_price' => 1000,
            'price' => 2000,
            'is_active' => true,
        ]);
        $inventory = app(\App\Services\InventoryService::class);

        $inventory->recordOpeningStock($product, [
            'warehouse_quantity' => 10,
            'display_quantity' => 0,
            'unit_cost' => 1000,
            'unit_price' => 2000,
            'occurred_at' => now()->subDays(2)->toDateString(),
        ]);
        $inventory->recordIntake($product, [
            'quantity' => 10,
            'unit_cost' => 1500,
            'unit_price' => 2000,
            'occurred_at' => now()->subDay()->toDateString(),
        ]);

        $transfer = $inventory->transferToDisplay($product, 12);
        $product->refresh();
        $batches = InventoryBatch::where('product_id', $product->id)->orderBy('received_at')->get();

        $this->assertSame(8, $product->warehouse_stock_quantity);
        $this->assertSame(12, $product->stock_quantity);
        $this->assertSame(0, $batches[0]->warehouse_remaining_quantity);
        $this->assertSame(10, $batches[0]->display_remaining_quantity);
        $this->assertSame(8, $batches[1]->warehouse_remaining_quantity);
        $this->assertSame(2, $batches[1]->display_remaining_quantity);
        $this->assertCount(2, $transfer->meta['transfer_breakdown']);

        $shoppingList = \App\Models\ShoppingList::create([
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'delivery_preference' => 'school',
            'source' => \App\Models\ShoppingList::SOURCE_CART,
            'cart_items' => [[
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => 2000,
                'quantity' => 11,
                'line_total' => 22000,
            ]],
            'items_subtotal' => 22000,
            'delivery_fee' => 0,
            'estimated_total' => 22000,
            'status' => \App\Models\ShoppingList::STATUS_QUOTED,
            'payment_status' => \App\Models\ShoppingList::PAYMENT_PENDING,
        ]);

        $inventory->recordPaidCartSale($shoppingList);
        $product->refresh();
        $batches = InventoryBatch::where('product_id', $product->id)->orderBy('received_at')->get();

        $this->assertSame(1, $product->stock_quantity);
        $this->assertSame(0, $batches[0]->remaining_quantity);
        $this->assertSame(0, $batches[0]->display_remaining_quantity);
        $this->assertSame(9, $batches[1]->remaining_quantity);
        $this->assertSame(1, $batches[1]->display_remaining_quantity);
        $this->assertDatabaseHas('shopping_list_items', [
            'shopping_list_id' => $shoppingList->id,
            'product_id' => $product->id,
            'cost_total' => 11500,
            'profit_total' => 10500,
        ]);
    }

    public function test_editing_old_stock_batch_does_not_reprice_current_product(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $product = Product::create([
            'name' => 'Counter Book',
            'slug' => 'counter-book-reprice',
            'sku' => 'EDK260900891',
            'cost_price' => 1000,
            'price' => 1500,
            'is_active' => true,
        ]);
        $inventory = app(\App\Services\InventoryService::class);
        $inventory->recordOpeningStock($product, [
            'warehouse_quantity' => 5,
            'display_quantity' => 0,
            'unit_cost' => 1000,
            'unit_price' => 1500,
            'occurred_at' => now()->subDays(2)->toDateString(),
        ]);
        $inventory->recordIntake($product, [
            'quantity' => 5,
            'unit_cost' => 1800,
            'unit_price' => 2500,
            'occurred_at' => now()->toDateString(),
        ]);
        $product->refresh();
        $this->assertSame(2500, (int) $product->price);

        $opening = InventoryMovement::where('product_id', $product->id)
            ->where('type', InventoryMovement::TYPE_OPENING_STOCK)
            ->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.inventory.movements.update', $opening), [
                'occurred_at' => now()->subDays(2)->toDateString(),
                'warehouse_quantity' => 5,
                'display_quantity' => 0,
                'unit_cost' => 900,
                'unit_price' => 1200,
            ])
            ->assertRedirect();

        $this->assertSame(2500, (int) $product->fresh()->price);
    }

    public function test_admin_can_import_and_export_products_with_inventory_csv(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $headers = \App\Services\InventoryImportService::HEADERS;
        $csv = implode(',', $headers)."\n"
            .'opening_stock,'.now()->toDateString().',,"Chalk Box","Stationery","Dustless chalk for classroom boards","Assorted","Box",3000,4500,20,5,3,1,0,"Opening stock import"';

        $this->actingAs($admin)
            ->post(route('admin.inventory.import'), [
                'inventory_csv' => UploadedFile::fake()->createWithContent('inventory.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $product = Product::where('name', 'Chalk Box')->firstOrFail();

        $this->assertMatchesRegularExpression('/^EDK\d{9}$/', $product->sku);
        $this->assertSame(20, $product->warehouse_stock_quantity);
        $this->assertSame(5, $product->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => InventoryMovement::TYPE_OPENING_STOCK,
            'quantity' => 25,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inventory.template'))
            ->assertOk()
            ->assertDownload('edukit-inventory-template.csv');

        $this->actingAs($admin)
            ->get(route('admin.inventory.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_super_admin_can_manage_schools_and_delivery_fees(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $district = District::create([
            'name' => 'Wakiso',
            'slug' => 'wakiso',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.schools.store'), [
                'district_id' => $district->id,
                'name' => 'Gayaza High School',
                'school_code' => 'UG-SCH-0001',
                'location' => 'Gayaza',
                'contact_person' => 'School Administrator',
                'contact_phone' => '+256700000001',
                'distance_from_warehouse_km' => 21,
                'delivery_fee' => 12000,
                'is_active' => '1',
            ])->assertRedirect(route('admin.schools.index'));

        $school = School::where('name', 'Gayaza High School')->firstOrFail();

        $this->assertSame('gayaza-high-school-wakiso', $school->slug);
        $this->assertSame(12000, $school->delivery_fee);
        $this->assertTrue($school->is_active);

        $this->actingAs($admin)
            ->get(route('admin.schools.index', ['q' => 'Gayaza', 'district_id' => $district->id, 'status' => 'active']))
            ->assertOk()
            ->assertSee('Gayaza High School')
            ->assertSee('UGX 12,000')
            ->assertSee(route('admin.schools.show', $school), false)
            ->assertSee(route('admin.schools.edit', $school), false)
            ->assertSee(route('admin.schools.destroy', $school), false);

        $this->actingAs($admin)
            ->put(route('admin.schools.update', $school), [
                'district_id' => $district->id,
                'name' => 'Gayaza High School',
                'school_code' => 'UG-SCH-0001',
                'location' => 'Gayaza main gate',
                'contact_person' => 'Bursar',
                'contact_phone' => '+256700000002',
                'distance_from_warehouse_km' => 22,
                'delivery_fee' => 15000,
                'is_active' => '1',
            ])->assertRedirect(route('admin.schools.index'));

        $school->refresh();

        $this->assertSame('Gayaza main gate', $school->location);
        $this->assertSame(15000, $school->delivery_fee);

        $this->actingAs($admin)
            ->get(route('admin.schools.show', $school))
            ->assertOk()
            ->assertSee('Gayaza High School')
            ->assertSee('UGX 15,000');

        $this->actingAs($admin)
            ->delete(route('admin.schools.destroy', $school))
            ->assertRedirect(route('admin.schools.index'));

        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
    }

    public function test_non_admin_cannot_access_admin_products(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_seeded_products_use_client_images_and_preserve_admin_edits(): void
    {
        $this->seed(EduKitProductSeeder::class);

        $product = Product::where('slug', 'oxford-mathematical-set')->firstOrFail();

        $this->assertSame(18000, (int) $product->price);
        $this->assertMatchesRegularExpression('/^EDK\d{9}$/', $product->sku);
        $this->assertSame('products/seed/oxford-math-set.png', $product->image_path);
        $this->assertStringContainsString('/images/products/oxford-math-set.png', $product->image_url);
        Storage::disk('public')->assertExists($product->image_path);

        $product->update([
            'price' => 20000,
            'description' => 'Admin updated this product after the initial seed.',
            'image_path' => '/images/products/oxford-math-set.png',
        ]);

        $this->seed(EduKitProductSeeder::class);

        $product->refresh();

        $this->assertSame(20000, (int) $product->price);
        $this->assertSame('Admin updated this product after the initial seed.', $product->description);
        $this->assertSame('products/seed/oxford-math-set.png', $product->image_path);
    }
}
