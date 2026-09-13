<?php

namespace Tests\Feature;

use App\Models\ShoppingList;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteProductFlowTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSessionHas('cart.'.$product->id, 1);

        $this->get(route('website.cart.index'))
            ->assertOk()
            ->assertSee('Blue School Bag')
            ->assertSee('UGX 60,000')
            ->assertSee('View order summary');
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

        $this->post(route('website.track-order.lookup'), [
            'reference' => strtolower($invoice->reference),
            'contact' => '+256700123456',
        ])->assertRedirect(route('website.quote.show', $invoice->reference));

        $this->post(route('website.track-order.lookup'), [
            'reference' => $invoice->reference,
            'contact' => 'wrong@example.test',
        ])->assertSessionHasErrors('reference');
    }
}
