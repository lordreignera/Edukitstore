<?php

namespace Tests\Feature;

use App\Models\ShoppingList;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OnboardingAndShoppingListFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_upload_school_shopping_list(): void
    {
        Storage::fake('local');

        $response = $this->post(route('website.upload-list.store'), [
            'parent_name' => 'Sarah N.',
            'phone' => '+256700123456',
            'email' => 'sarah@example.test',
            'school_name' => 'Kampala Primary School',
            'learner_name' => 'Ariella',
            'class_level' => 'P.5',
            'delivery_preference' => 'school',
            'delivery_location' => 'Kampala Primary main gate',
            'notes' => 'Please quote books and toiletries first.',
            'shopping_list' => UploadedFile::fake()->create('school-list.pdf', 120, 'application/pdf'),
        ]);

        $response
            ->assertRedirect(route('website.upload-list'))
            ->assertSessionHas('status');

        $shoppingList = ShoppingList::firstOrFail();

        $this->assertSame('Sarah N.', $shoppingList->parent_name);
        $this->assertSame(ShoppingList::STATUS_PENDING, $shoppingList->status);
        Storage::disk('local')->assertExists($shoppingList->file_path);
    }

    public function test_supplier_can_submit_onboarding_application(): void
    {
        Storage::fake('local');

        $response = $this->post(route('website.suppliers.store'), [
            'business_name' => 'Nakasero Scholastic Stores',
            'contact_person' => 'Daniel K.',
            'phone' => '+256701222333',
            'email' => 'supply@example.test',
            'district' => 'Kampala',
            'address' => 'Nakasero Market',
            'product_categories' => 'Exercise books, pens, mathematical sets',
            'supply_capacity' => '300 orders per week',
            'notes' => 'We can fulfil urgent stationery orders.',
            'verification_document' => UploadedFile::fake()->create('business-license.pdf', 90, 'application/pdf'),
        ]);

        $response
            ->assertRedirect(route('website.suppliers'))
            ->assertSessionHas('status');

        $supplier = Supplier::firstOrFail();

        $this->assertSame('website', $supplier->source);
        $this->assertFalse($supplier->is_approved);
        $this->assertSame('Exercise books, pens, mathematical sets', $supplier->product_categories);
        Storage::disk('local')->assertExists($supplier->verification_document_path);
    }

    public function test_admin_can_review_uploaded_shopping_list(): void
    {
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $shoppingList = ShoppingList::create([
            'parent_name' => 'Sarah N.',
            'phone' => '+256700123456',
            'school_name' => 'Kampala Primary School',
            'delivery_preference' => 'school',
            'file_path' => 'shopping-lists/school-list.pdf',
            'original_filename' => 'school-list.pdf',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.shopping-lists.update', $shoppingList), [
                'status' => ShoppingList::STATUS_QUOTED,
                'estimated_total' => 145000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shopping_lists', [
            'id' => $shoppingList->id,
            'status' => ShoppingList::STATUS_QUOTED,
            'estimated_total' => 145000,
            'reviewed_by' => $admin->id,
        ]);
    }
}
