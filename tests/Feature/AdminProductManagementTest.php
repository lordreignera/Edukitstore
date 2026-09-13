<?php

namespace Tests\Feature;

use App\Models\District;
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
            'price' => 28000,
            'stock_quantity' => 25,
            'image' => UploadedFile::fake()->image('science-revision.jpg', 900, 700),
            'is_active' => '1',
            'is_featured' => '1',
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'P.7 Integrated Science Revision Book')->firstOrFail();

        $this->assertMatchesRegularExpression('/^EDK\d{9}$/', $product->sku);
        $this->assertNotSame('MANUAL-CODE-SHOULD-BE-IGNORED', $product->sku);
        $this->assertSame(28000, (int) $product->price);
        $this->assertTrue($product->is_active);
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);

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
                'price' => 6000,
                'stock_quantity' => 75,
                'image' => UploadedFile::fake()->image('new-cover.png', 800, 800),
                'is_active' => '1',
            ])->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('EDK260900003', $product->sku);
        $this->assertSame('Counter Book A4', $product->name);
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
