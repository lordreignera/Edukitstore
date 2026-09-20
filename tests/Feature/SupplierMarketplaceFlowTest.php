<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\Supplier;
use App\Models\SupplierOffer;
use App\Models\User;
use App\Services\MarketplaceSourceService;
use App\Services\SupplierSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierMarketplaceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_submits_stock_and_admin_approves_it_into_the_shared_catalogue(): void
    {
        [$supplierUser, $supplier, $category] = $this->supplierContext();
        Storage::fake(Product::imageDisk());

        $this->actingAs($supplierUser)->post(route('supplier.offers.store'), [
            'product_category_id' => $category->id,
            'submitted_name' => 'Supplier Exercise Book',
            'submitted_description' => 'A4 ruled exercise book',
            'submitted_unit' => 'book',
            'supplier_price' => 1600,
            'quantity_submitted' => 100,
            'reorder_level' => 10,
            'image' => UploadedFile::fake()->image('exercise-book.jpg', 800, 800),
        ])->assertRedirect(route('supplier.offers.index'));

        $offer = SupplierOffer::firstOrFail();
        $this->assertSame(100, $offer->pending_quantity);
        $this->assertSame(0, $offer->quantity_available);

        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('admin.supplier-offers.approve', $offer), [
            'customer_price' => 1800,
            'approved_quantity' => 100,
        ])->assertRedirect();

        $offer->refresh();
        $this->assertSame(SupplierOffer::STATUS_APPROVED, $offer->status);
        $this->assertSame(0, $offer->pending_quantity);
        $this->assertSame(100, $offer->quantity_available);
        $this->assertNotNull($offer->product_id);
        $this->assertNotNull($offer->submitted_image_path);
        $this->assertDatabaseCount('products', 1);

        $product = $offer->product;
        $this->assertSame(0, $product->stock_quantity);
        $source = app(MarketplaceSourceService::class)->source($product);
        $this->assertSame('supplier', $source['type']);
        $this->assertSame($supplier->id, $source['offer']->supplier_id);
        $this->assertSame(1800.0, $source['price']);
    }

    public function test_master_product_selection_reuses_identity_and_prevents_supplier_duplicates(): void
    {
        [$supplierUser, $supplier, $category] = $this->supplierContext();
        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Master Geometry Set',
            'slug' => 'master-geometry-set',
            'sku' => 'EDK-MT-0001',
            'description' => 'Approved catalogue description',
            'image_path' => 'products/master-set.jpg',
            'price' => 18000,
            'is_active' => true,
        ]);

        $payload = [
            'product_id' => $product->id,
            'submitted_name' => 'Attempted renamed product',
            'supplier_price' => 15000,
            'quantity_submitted' => 20,
            'reorder_level' => 4,
        ];

        $this->actingAs($supplierUser)->post(route('supplier.offers.store'), $payload)->assertRedirect(route('supplier.offers.index'));
        $offer = SupplierOffer::firstOrFail();
        $this->assertSame('Master Geometry Set', $offer->submitted_name);
        $this->assertSame('Approved catalogue description', $offer->submitted_description);
        $this->assertNull($offer->submitted_image_path);

        $this->actingAs($supplierUser)->from(route('supplier.offers.create'))->post(route('supplier.offers.store'), $payload)
            ->assertRedirect(route('supplier.offers.create'))
            ->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('supplier_offers', 1);
    }

    public function test_supplier_sale_tracks_supplier_payable_and_edukit_margin_without_touching_edukit_stock(): void
    {
        [, $supplier, $category] = $this->supplierContext();
        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Direct Supply Book',
            'slug' => 'direct-supply-book',
            'sku' => 'EDK-BK-0001',
            'cost_price' => 0,
            'price' => 1800,
            'warehouse_stock_quantity' => 40,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        $offer = SupplierOffer::create([
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'product_category_id' => $category->id,
            'submitted_name' => $product->name,
            'supplier_price' => 1600,
            'customer_price' => 1800,
            'quantity_submitted' => 50,
            'pending_quantity' => 0,
            'quantity_available' => 50,
            'status' => SupplierOffer::STATUS_APPROVED,
            'direct_fulfilment' => true,
        ]);
        $invoice = ShoppingList::create([
            'parent_name' => 'Parent One',
            'phone' => '0700000001',
            'reference' => 'EDK-260920-1000',
            'source' => ShoppingList::SOURCE_CART,
            'cart_items' => [[
                'product_id' => $product->id,
                'supplier_offer_id' => $offer->id,
                'supplier_id' => $supplier->id,
                'fulfilment_source' => 'supplier',
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => 1800,
                'quantity' => 3,
                'line_total' => 5400,
            ]],
            'items_subtotal' => 5400,
            'estimated_total' => 5400,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
        ]);

        app(SupplierSaleService::class)->recordPaidSale($invoice);

        $offer->refresh();
        $product->refresh();
        $line = ShoppingListItem::firstOrFail();
        $this->assertSame(47, $offer->quantity_available);
        $this->assertSame(3, $offer->quantity_sold);
        $this->assertSame(40, $product->warehouse_stock_quantity);
        $this->assertSame(0, $product->stock_quantity);
        $this->assertSame('4800.00', $line->supplier_payable);
        $this->assertSame('600.00', $line->profit_total);
        $this->assertSame('reserved', $line->settlement_status);
    }

    public function test_driver_confirmation_completes_order_and_earns_supplier_settlement(): void
    {
        Role::findOrCreate('delivery-person');
        $driverUser = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $driverUser->assignRole('delivery-person');
        $driver = Driver::create(['user_id' => $driverUser->id, 'name' => 'Assigned Driver', 'is_approved' => true, 'is_available' => true]);
        $supplier = Supplier::create(['business_name' => 'Delivery Supplier', 'is_approved' => true, 'is_active' => true]);
        $invoice = ShoppingList::create([
            'parent_name' => 'Parent Two',
            'phone' => '0700000002',
            'reference' => 'EDK-260920-1001',
            'assigned_driver_id' => $driver->id,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
        ]);
        ShoppingListItem::create([
            'shopping_list_id' => $invoice->id,
            'supplier_id' => $supplier->id,
            'fulfilment_source' => 'supplier',
            'product_name' => 'Supplier Book',
            'quantity' => 1,
            'unit_cost' => 1600,
            'unit_price' => 1800,
            'line_total' => 1800,
            'cost_total' => 1600,
            'profit_total' => 200,
            'supplier_payable' => 1600,
            'settlement_status' => 'reserved',
        ]);

        $this->actingAs($driverUser)->patch(route('driver.deliveries.confirm', $invoice))->assertRedirect();

        $this->assertSame(ShoppingList::STATUS_FULFILLED, $invoice->fresh()->status);
        $this->assertSame('earned', $invoice->lineItems()->first()->settlement_status);
    }

    private function supplierContext(): array
    {
        Role::findOrCreate('supplier');
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->assignRole('supplier');
        $supplier = Supplier::create([
            'user_id' => $user->id,
            'business_name' => 'Kampala School Supplies',
            'district' => 'Kampala',
            'local_delivery_fee' => 5000,
            'other_district_delivery_fee' => 12000,
            'is_approved' => true,
            'is_active' => true,
        ]);
        $category = ProductCategory::create(['name' => 'Books', 'slug' => 'books', 'is_active' => true]);

        return [$user, $supplier, $category];
    }

    private function admin(): User
    {
        $role = Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $admin->assignRole($role);

        return $admin;
    }
}
