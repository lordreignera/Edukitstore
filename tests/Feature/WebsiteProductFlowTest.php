<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'image_url' => '/images/products/exercise-book-96.svg',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Everything for their education')
            ->assertSee('Ugandan Exercise Book 96 Pages')
            ->assertSee('UGX 2,500');
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
            'image_url' => '/images/products/black-school-shoes.svg',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->get('/products?search=shoes')
            ->assertOk()
            ->assertSee('Black Leather School Shoes')
            ->assertSee('UGX 45,000');
    }

    public function test_customer_can_add_product_to_session_cart(): void
    {
        $product = Product::create([
            'name' => 'Blue School Bag',
            'slug' => 'blue-school-bag',
            'sku' => 'TEST-BAG',
            'price' => 60000,
            'stock_quantity' => 10,
            'image_url' => '/images/products/blue-school-bag.svg',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->post(route('website.cart.store', $product))
            ->assertSessionHas('cart.'.$product->id, 1);

        $this->get(route('website.cart.index'))
            ->assertOk()
            ->assertSee('Blue School Bag')
            ->assertSee('UGX 60,000');
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
            ->assertSee('Order tracking is coming soon');
    }
}
