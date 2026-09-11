<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\EduKitProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_product_for_public_catalogue(): void
    {
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
            'sku' => 'TEST-SCI-P7',
            'description' => 'Revision book for Ugandan primary candidates.',
            'brand' => 'EduKit Books',
            'unit' => 'Book',
            'price' => 28000,
            'stock_quantity' => 25,
            'image_url' => '/images/products/dictionary.svg',
            'is_active' => '1',
            'is_featured' => '1',
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-SCI-P7',
            'price' => 28000,
            'is_active' => true,
        ]);

        $this->get(route('website.products.index'))
            ->assertOk()
            ->assertSee('P.7 Integrated Science Revision Book')
            ->assertSee('UGX 28,000');
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

        $product = Product::where('sku', 'EDK-DOC-OXFORD-MATH-SET')->firstOrFail();

        $this->assertSame(18000, (int) $product->price);
        $this->assertSame('/images/products/oxford-math-set.png', $product->image_url);
        $this->assertFileExists(public_path(ltrim($product->image_url, '/')));

        $product->update([
            'price' => 20000,
            'description' => 'Admin updated this product after the initial seed.',
        ]);

        $this->seed(EduKitProductSeeder::class);

        $product->refresh();

        $this->assertSame(20000, (int) $product->price);
        $this->assertSame('Admin updated this product after the initial seed.', $product->description);
    }
}
