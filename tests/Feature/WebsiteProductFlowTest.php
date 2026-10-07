<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\School;
use App\Models\ShoppingList;
use App\Models\Supplier;
use App\Models\SupplierOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteProductFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_sort_uses_the_price_shown_for_supplier_stock(): void
    {
        $owned = Product::create(['name' => 'Own stock', 'slug' => 'own-stock', 'sku' => 'OWN-1', 'price' => 3000, 'stock_quantity' => 2, 'is_active' => true]);
        $supplied = Product::create(['name' => 'Supplier stock', 'slug' => 'supplier-stock', 'sku' => 'SUP-1', 'price' => 1000, 'is_active' => true]);
        $supplier = Supplier::create(['business_name' => 'Book supplier', 'is_approved' => true, 'is_active' => true]);
        SupplierOffer::create([
            'supplier_id' => $supplier->id, 'product_id' => $supplied->id,
            'submitted_name' => $supplied->name, 'supplier_price' => 4000,
            'customer_price' => 5000, 'quantity_submitted' => 2,
            'quantity_available' => 2, 'status' => SupplierOffer::STATUS_APPROVED,
            'direct_fulfilment' => true,
        ]);

        $this->get(route('website.products.index', ['sort' => 'price_low']))
            ->assertOk()->assertSeeInOrder([$owned->name, $supplied->name]);
    }

    public function test_homepage_displays_featured_admin_products(): void
    {
        $category = ProductCategory::create([
            'name' => 'Stationery',
            'slug' => 'stationery',
            'is_active' => true,
        ]);

        Product::create([
            'product_category_id' => $category->id,
            'name' => 'Ugandan Exercise Book 96 Pages',
            'slug' => 'ugandan-exercise-book-96-pages',
            'sku' => 'TEST-EX96',
            'price' => 2500,
            'stock_quantity' => 40,
            'image_path' => 'products/test/exercise-book-96.svg',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Everything for their education')
            ->assertSee('Ugandan Exercise Book 96 Pages')
            ->assertSee('UGX 2,500')
            ->assertSee('Mobile apps are being prepared');
    }

    public function test_product_search_displays_matching_products(): void
    {
        $category = ProductCategory::create([
            'name' => 'Shoes',
            'slug' => 'shoes',
            'is_active' => true,
        ]);

        Product::create([
            'product_category_id' => $category->id,
            'name' => 'Black Leather School Shoes',
            'slug' => 'black-leather-school-shoes',
            'sku' => 'TEST-SHOES',
            'price' => 45000,
            'stock_quantity' => 12,
            'image_path' => 'products/test/black-school-shoes.svg',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->get('/products?search=shoes')
            ->assertOk()
            ->assertSee('Black Leather School Shoes')
            ->assertSee('UGX 45,000');
    }

    public function test_live_suggestions_include_only_active_matching_products(): void
    {
        Product::create(['name' => 'Black School Shoes', 'slug' => 'black-school-shoes', 'sku' => 'SHOES-1', 'price' => 45000, 'stock_quantity' => 2, 'is_active' => true]);
        Product::create(['name' => 'Old School Shoes', 'slug' => 'old-school-shoes', 'sku' => 'SHOES-2', 'price' => 30000, 'stock_quantity' => 0, 'is_active' => false]);

        $this->getJson(route('website.products.suggest', ['search' => 'shoes']))
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', 'Black School Shoes');
    }

    public function test_product_catalogue_can_sort_by_lowest_price(): void
    {
        Product::create([
            'name' => 'Premium School Bag',
            'slug' => 'premium-school-bag',
            'sku' => 'TEST-PREMIUM-BAG',
            'price' => 90000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Budget Exercise Book',
            'slug' => 'budget-exercise-book',
            'sku' => 'TEST-BUDGET-BOOK',
            'price' => 2500,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->get('/products?sort=price_low')
            ->assertOk()
            ->assertSee('Shop school supplies')
            ->assertSee('Departments');

        $this->assertStringContainsString('Budget Exercise Book', $response->getContent());
        $this->assertLessThan(
            strpos($response->getContent(), 'Premium School Bag'),
            strpos($response->getContent(), 'Budget Exercise Book')
        );
    }

    public function test_search_ignores_stale_category_filter(): void
    {
        $bags = ProductCategory::create([
            'name' => 'Bags',
            'slug' => 'bags',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'Bedding and Linen',
            'slug' => 'bedding-and-linen',
            'is_active' => true,
        ]);

        Product::create([
            'product_category_id' => $bags->id,
            'name' => 'Blue School Backpack',
            'slug' => 'blue-school-backpack',
            'sku' => 'EDK260900100',
            'price' => 60000,
            'stock_quantity' => 10,
            'image_path' => 'products/test/blue-school-bag.svg',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->get('/products?search=bags&category=bedding-and-linen')
            ->assertOk()
            ->assertSee('Blue School Backpack')
            ->assertDontSee('No products match this search.');
    }

    public function test_customer_can_add_product_to_session_cart(): void
    {
        $product = Product::create([
            'name' => 'Blue School Bag',
            'slug' => 'blue-school-bag',
            'sku' => 'TEST-BAG',
            'price' => 60000,
            'stock_quantity' => 10,
            'image_path' => 'products/test/blue-school-bag.svg',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->post(route('website.cart.store', $product))
            ->assertSessionHas('cart.'.$product->id, 1)
            ->assertSessionHas('cart_added');

        $this->get(route('website.products.index'))
            ->assertOk()
            ->assertSee('Checkout')
            ->assertSee(route('website.cart.index').'#order-details', false);

        $this->get(route('website.cart.index'))
            ->assertOk()
            ->assertSee('Blue School Bag')
            ->assertSee('UGX 60,000')
            ->assertSee('Create and view invoice');
    }

    public function test_checkout_button_adds_product_and_opens_cart_details(): void
    {
        $product = Product::create([
            'name' => 'Canvas School Shoes', 'slug' => 'canvas-school-shoes',
            'sku' => 'SHOES-CANVAS', 'price' => 35000,
            'stock_quantity' => 5, 'is_active' => true,
        ]);

        $this->get(route('website.products.show', $product))
            ->assertOk()
            ->assertSee('name="checkout" value="1"', false);

        $this->post(route('website.cart.store', $product), ['quantity' => 2, 'checkout' => '1'])
            ->assertRedirect(route('website.cart.index').'#order-details')
            ->assertSessionHas('cart.'.$product->id, 2);

        $this->get(route('website.cart.index'))
            ->assertOk()
            ->assertSee('Canvas School Shoes')
            ->assertSee('Convenience fee')
            ->assertSee('Pay now');
    }

    public function test_switching_fulfilment_source_replaces_the_old_cart_quantity(): void
    {
        $product = Product::create([
            'name' => 'School Bag', 'slug' => 'school-bag-sources',
            'sku' => 'BAG-SOURCES', 'price' => 60000,
            'stock_quantity' => 5, 'is_active' => true,
        ]);
        $supplier = Supplier::create([
            'business_name' => 'Bag Supplier', 'is_approved' => true, 'is_active' => true,
        ]);
        $offer = SupplierOffer::create([
            'supplier_id' => $supplier->id, 'product_id' => $product->id,
            'submitted_name' => $product->name, 'supplier_price' => 40000,
            'customer_price' => 55000, 'quantity_submitted' => 4,
            'quantity_available' => 4, 'status' => SupplierOffer::STATUS_APPROVED,
            'direct_fulfilment' => true,
        ]);

        $this->post(route('website.cart.store', $product), ['quantity' => 3])
            ->assertSessionHas('cart.'.$product->id, 3);
        $this->post(route('website.cart.store', $product), [
            'quantity' => 1, 'supplier_offer_id' => $offer->id,
        ])->assertSessionHas('cart.'.$product->id, 1)
            ->assertSessionHas('cart_sources.'.$product->id, $offer->id);
        $this->post(route('website.cart.store', $product), [
            'quantity' => 1, 'supplier_offer_id' => $offer->id,
        ])->assertSessionHas('cart.'.$product->id, 2);
        $this->post(route('website.cart.store', $product), ['quantity' => 1])
            ->assertSessionHas('cart.'.$product->id, 1);
    }

    public function test_cart_removes_unavailable_items_and_reduces_quantity_to_live_stock(): void
    {
        $available = Product::create([
            'name' => 'Available Book', 'slug' => 'available-book-cart',
            'sku' => 'BOOK-CART', 'price' => 5000, 'stock_quantity' => 2,
            'is_active' => true,
        ]);
        $archived = Product::create([
            'name' => 'Archived Bag', 'slug' => 'archived-bag-cart',
            'sku' => 'BAG-ARCHIVED', 'price' => 10000,
            'stock_quantity' => 3, 'is_active' => false,
        ]);

        $this->withSession(['cart' => [$available->id => 4, $archived->id => 1]])
            ->get(route('website.cart.index'))
            ->assertOk()
            ->assertSee('Available Book')
            ->assertDontSee('Archived Bag')
            ->assertSee('Some cart items or quantities changed')
            ->assertSessionHas('cart.'.$available->id, 2)
            ->assertSessionMissing('cart.'.$archived->id);
    }

    public function test_pickup_checkout_does_not_require_a_delivery_location(): void
    {
        $product = Product::create([
            'name' => 'Pickup Book', 'slug' => 'pickup-book',
            'sku' => 'BOOK-PICKUP', 'price' => 5000,
            'stock_quantity' => 2, 'is_active' => true,
        ]);
        $this->post(route('website.cart.store', $product), ['quantity' => 1]);

        $this->post(route('website.cart.submit'), [
            'parent_name' => 'Pickup Parent', 'phone' => '0700000001',
            'delivery_preference' => 'pickup',
        ])->assertRedirect();

        $invoice = ShoppingList::firstOrFail();
        $this->assertSame('EduKit warehouse pickup', $invoice->delivery_location);
        $this->assertSame(0, $invoice->delivery_fee);
    }

    public function test_cart_checkout_uses_selected_school_fee_and_is_ready_for_payment(): void
    {
        $district = District::create([
            'name' => 'Wakiso',
            'slug' => 'wakiso',
            'is_active' => true,
        ]);

        $school = School::create([
            'district_id' => $district->id,
            'name' => 'Gayaza High School',
            'slug' => 'gayaza-high-school-wakiso',
            'location' => 'Gayaza',
            'distance_from_warehouse_km' => 21,
            'delivery_fee' => 12000,
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'School Backpack',
            'slug' => 'school-backpack',
            'sku' => 'TEST-BAG',
            'price' => 60000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->post(route('website.cart.store', $product), ['quantity' => 1]);

        $this->post(route('website.cart.submit'), [
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'email' => 'norah@example.test',
            'delivery_preference' => 'school',
            'school_id' => $school->id,
            'learner_name' => 'Sarah Nakato',
            'class_level' => 'S2',
        ])->assertRedirect();

        $invoice = ShoppingList::firstOrFail();

        $this->assertSame(ShoppingList::STATUS_QUOTED, $invoice->status);
        $this->assertSame(ShoppingList::PAYMENT_UNPAID, $invoice->payment_status);
        $this->assertSame($school->id, $invoice->school_id);
        $this->assertSame($district->id, $invoice->district_id);
        $this->assertSame(60000, $invoice->items_subtotal);
        $this->assertSame(12000, $invoice->delivery_fee);
        $this->assertSame(72000, $invoice->estimated_total);

        $this->get(route('website.quote.show', $invoice->reference))
            ->assertOk()
            ->assertSee('Gayaza High School')
            ->assertSee('Convenience fee')
            ->assertSee('UGX 72,000')
            ->assertSee('Pay now');
    }

    public function test_cart_supports_legacy_public_product_image_paths(): void
    {
        $product = Product::create([
            'name' => 'Files and Folders Assortment',
            'slug' => 'files-and-folders-assortment',
            'sku' => 'TEST-FILES',
            'price' => 10000,
            'stock_quantity' => 10,
            'image_path' => '/images/products/files-and-folders.jpg',
            'is_active' => true,
        ]);

        $this->post(route('website.cart.store', $product));

        $this->get(route('website.cart.index'))
            ->assertOk()
            ->assertSee('/images/products/files-and-folders.jpg', false);
    }

    public function test_product_image_url_falls_back_to_seed_disk_for_missing_public_image(): void
    {
        Storage::fake('s3');
        config([
            'filesystems.product_images_disk' => 's3',
            'filesystems.disks.s3.url' => 'https://cdn.edukit.test',
        ]);

        Storage::disk('s3')->put('products/seed/cloud-only-product.png', 'image');

        $product = Product::create([
            'name' => 'Cloud Only Product',
            'slug' => 'cloud-only-product',
            'sku' => 'TEST-CLOUD',
            'price' => 10000,
            'stock_quantity' => 10,
            'image_path' => '/images/products/cloud-only-product.png',
            'is_active' => true,
        ]);

        $this->assertStringContainsString('/products/seed/cloud-only-product.png', $product->image_url);
    }

    public function test_coming_soon_pages_are_real_destinations(): void
    {
        $this->get(route('website.upload-list'))
            ->assertOk()
            ->assertSee('Send the list. We prepare the basket.');

        $this->get(route('website.suppliers'))
            ->assertOk()
            ->assertSee('Supply school essentials across Uganda.');

        $this->get(route('website.track-order'))
            ->assertOk()
            ->assertSee('Check if your EduKit invoice is ready.');
    }

    public function test_customer_can_lookup_invoice_by_reference_and_contact(): void
    {
        $invoice = ShoppingList::create([
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'email' => 'norah@example.test',
            'delivery_preference' => 'school',
            'status' => ShoppingList::STATUS_QUOTED,
        ]);

        $this->get(route('website.quote.show', $invoice->reference))->assertNotFound();

        $this->post(route('website.track-order.lookup'), [
            'reference' => strtolower($invoice->reference),
            'contact' => '0700123456',
        ])->assertRedirect(route('website.quote.show', $invoice->reference));

        $this->get(route('website.quote.show', $invoice->reference))->assertOk();

        $this->post(route('website.track-order.lookup'), [
            'reference' => $invoice->reference,
            'contact' => 'wrong@example.test',
        ])->assertSessionHasErrors('reference');
    }

    public function test_customer_can_recover_multiple_orders_without_order_number(): void
    {
        $first = ShoppingList::create([
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'email' => 'norah@example.test',
            'delivery_preference' => 'school',
            'source' => ShoppingList::SOURCE_CART,
            'items_subtotal' => 45000,
            'delivery_fee' => 5000,
            'estimated_total' => 50000,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
        ]);
        $second = ShoppingList::create([
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'email' => 'norah@example.test',
            'delivery_preference' => 'school',
            'source' => ShoppingList::SOURCE_CART,
            'items_subtotal' => 80000,
            'delivery_fee' => 10000,
            'estimated_total' => 90000,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);

        $this->post(route('website.track-order.recover'), ['contact' => '0700 123 456'])
            ->assertOk()
            ->assertSee($first->reference)
            ->assertSee($second->reference)
            ->assertSee('Choose an order to track')
            ->assertSee('UGX 50,000')
            ->assertSee('UGX 90,000');

        $this->assertTrue((bool) session('invoice_access.'.$first->id));
        $this->assertTrue((bool) session('invoice_access.'.$second->id));
    }

    public function test_customer_is_told_when_order_recovery_has_no_matches(): void
    {
        $this->from(route('website.track-order'))
            ->post(route('website.track-order.recover'), ['contact' => 'no-order@example.test'])
            ->assertRedirect(route('website.track-order'))
            ->assertSessionHasErrors('contact');
    }
}
