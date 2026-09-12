<?php

namespace Tests\Feature;

use App\Models\ShoppingList;
use App\Models\Driver;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OnboardingAndShoppingListFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_partner_can_submit_an_application_for_admin_review(): void
    {
        Storage::fake('local');

        $this->post(route('website.drivers.store'), [
            'name' => 'Moses Kato',
            'phone' => '+256700000001',
            'email' => 'moses.driver@example.com',
            'district' => 'Wakiso',
            'vehicle_type' => 'Motorcycle',
            'vehicle_registration' => 'UEX 123A',
            'verification_document' => UploadedFile::fake()->create('permit.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('website.drivers'));

        $driver = Driver::where('email', 'moses.driver@example.com')->firstOrFail();
        $this->assertFalse($driver->is_approved);
        $this->assertSame('website', $driver->source);
        Storage::disk('local')->assertExists($driver->verification_document_path);
    }

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

        $shoppingList = ShoppingList::firstOrFail();

        $response
            ->assertRedirect(route('website.quote.show', $shoppingList->reference))
            ->assertSessionHas('status');

        $this->assertSame('Sarah N.', $shoppingList->parent_name);
        $this->assertSame(ShoppingList::SOURCE_UPLOAD, $shoppingList->source);
        $this->assertSame(ShoppingList::STATUS_PENDING, $shoppingList->status);
        $this->assertSame(ShoppingList::PAYMENT_UNPAID, $shoppingList->payment_status);
        Storage::disk('local')->assertExists($shoppingList->file_path);
    }

    public function test_invoice_references_use_daily_number_sequence(): void
    {
        $first = ShoppingList::create([
            'parent_name' => 'Sarah N.',
            'phone' => '+256700123456',
            'delivery_preference' => 'school',
        ]);

        $second = ShoppingList::create([
            'parent_name' => 'Daniel K.',
            'phone' => '+256700999888',
            'delivery_preference' => 'home',
        ]);

        $prefix = 'EDK-'.now()->format('ymd');

        $this->assertSame("{$prefix}-1000", $first->reference);
        $this->assertSame("{$prefix}-1001", $second->reference);
    }

    public function test_supplier_can_submit_onboarding_application(): void
    {
        Storage::fake('local');

        $books = ProductCategory::create([
            'name' => 'Books',
            'slug' => 'books',
            'is_active' => true,
        ]);
        $stationery = ProductCategory::create([
            'name' => 'Stationery',
            'slug' => 'stationery',
            'is_active' => true,
        ]);

        $response = $this->post(route('website.suppliers.store'), [
            'business_name' => 'Nakasero Scholastic Stores',
            'contact_person' => 'Daniel K.',
            'phone' => '+256701222333',
            'email' => 'supply@example.test',
            'district' => 'Kampala',
            'password' => 'SupplierPass123!',
            'password_confirmation' => 'SupplierPass123!',
            'address' => 'Nakasero Market',
            'product_category_ids' => [$books->id, $stationery->id],
            'other_product_categories' => 'Mathematical sets',
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
        $this->assertFalse($supplier->is_active);
        $this->assertNotNull($supplier->user_id);
        $this->assertFalse($supplier->user->is_active);
        $this->assertTrue(Hash::check('SupplierPass123!', $supplier->user->password));
        $this->assertSame('Books, Stationery, Mathematical sets', $supplier->product_categories);
        Storage::disk('local')->assertExists($supplier->verification_document_path);

        $this->post('/login', ['email' => 'supply@example.test', 'password' => 'SupplierPass123!'])
            ->assertSessionHasErrors('email');

        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin)->patch(route('admin.suppliers.approve', $supplier))->assertRedirect();
        $this->post(route('logout'));

        $this->post('/login', ['email' => 'supply@example.test', 'password' => 'SupplierPass123!'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_supplier_verification_document_uses_configured_documents_disk(): void
    {
        Storage::fake('s3');
        config(['filesystems.documents_disk' => 's3']);

        $category = ProductCategory::create([
            'name' => 'Books',
            'slug' => 'books',
            'is_active' => true,
        ]);

        $this->post(route('website.suppliers.store'), [
            'business_name' => 'Kikuubo Stationers',
            'contact_person' => 'Sarah K.',
            'phone' => '+256701222444',
            'email' => 'kikuubo@example.test',
            'district' => 'Kampala',
            'password' => 'SupplierPass123!',
            'password_confirmation' => 'SupplierPass123!',
            'product_category_ids' => [$category->id],
            'verification_document' => UploadedFile::fake()->create('license.pdf', 90, 'application/pdf'),
        ])->assertRedirect(route('website.suppliers'));

        $supplier = Supplier::firstOrFail();

        Storage::disk('s3')->assertExists($supplier->verification_document_path);
    }

    public function test_admin_can_review_uploaded_shopping_list(): void
    {
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $driver = Driver::create([
            'name' => 'John Driver',
            'phone' => '+256701111222',
            'email' => 'john.driver@example.test',
            'is_approved' => true,
            'is_available' => true,
        ]);

        $shoppingList = ShoppingList::create([
            'parent_name' => 'Sarah N.',
            'phone' => '+256700123456',
            'school_name' => 'Kampala Primary School',
            'delivery_preference' => 'school',
            'file_path' => 'shopping-lists/school-list.pdf',
            'original_filename' => 'school-list.pdf',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.invoices.update', $shoppingList), [
                'status' => ShoppingList::STATUS_QUOTED,
                'estimated_total' => 145000,
                'assigned_driver_id' => $driver->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shopping_lists', [
            'id' => $shoppingList->id,
            'status' => ShoppingList::STATUS_QUOTED,
            'estimated_total' => 145000,
            'assigned_driver_id' => $driver->id,
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_customer_submits_cart_for_admin_invoice_review(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $driver = Driver::create([
            'name' => 'John Driver',
            'phone' => '+256701111222',
            'email' => 'john.driver@example.test',
            'vehicle_type' => 'Motorcycle',
            'vehicle_registration' => 'UED 230A',
            'is_approved' => true,
            'is_available' => true,
        ]);

        $category = ProductCategory::create([
            'name' => 'Bags',
            'slug' => 'bags',
            'is_active' => true,
        ]);
        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'School Backpack',
            'slug' => 'school-backpack',
            'sku' => 'EDK260900001',
            'price' => 60000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->post(route('website.cart.store', $product), ['quantity' => 2])
            ->assertSessionHas('cart.'.$product->id, 2);

        $response = $this->post(route('website.cart.submit'), [
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'email' => 'norah@example.test',
            'school_name' => 'Gayaza High School',
            'learner_name' => 'Sarah Nakato',
            'class_level' => 'S2',
            'delivery_preference' => 'school',
            'delivery_location' => 'Gayaza High School reception',
            'notes' => 'Please deliver during school hours.',
        ]);

        $shoppingList = ShoppingList::firstOrFail();

        $response->assertRedirect(route('website.quote.show', $shoppingList->reference));
        $this->assertSame(ShoppingList::SOURCE_CART, $shoppingList->source);
        $this->assertSame(120000, $shoppingList->items_subtotal);
        $this->assertNull($shoppingList->delivery_fee);
        $this->assertNull($shoppingList->estimated_total);
        $this->assertSame(2, $shoppingList->cart_items[0]['quantity']);
        $this->assertSame(ShoppingList::PAYMENT_UNPAID, $shoppingList->payment_status);
        $this->assertFalse(session()->has('cart'));

        $this->actingAs($admin)
            ->patch(route('admin.invoices.update', $shoppingList), [
                'status' => ShoppingList::STATUS_QUOTED,
                'delivery_fee' => 15000,
                'assigned_driver_id' => $driver->id,
            ])->assertRedirect();

        $shoppingList->refresh();

        $this->assertSame(15000, $shoppingList->delivery_fee);
        $this->assertSame(135000, $shoppingList->estimated_total);
        $this->assertSame($driver->id, $shoppingList->assigned_driver_id);

        $this->get(route('website.quote.show', $shoppingList->reference))
            ->assertOk()
            ->assertSee('UGX 135,000')
            ->assertSee('John Driver')
            ->assertSee('+256701111222')
            ->assertSee('Pay with Flutterwave');
    }

    public function test_admin_cannot_complete_delivery_without_driver_confirmation(): void
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $driver = Driver::create([
            'name' => 'John Driver',
            'phone' => '+256701111222',
            'email' => 'john.driver@example.test',
            'is_approved' => true,
            'is_available' => true,
        ]);

        $shoppingList = ShoppingList::create([
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'delivery_preference' => 'school',
            'source' => ShoppingList::SOURCE_CART,
            'items_subtotal' => 120000,
            'delivery_fee' => 15000,
            'estimated_total' => 135000,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'assigned_driver_id' => $driver->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.invoices.update', $shoppingList), [
                'status' => ShoppingList::STATUS_FULFILLED,
                'delivery_fee' => 15000,
                'assigned_driver_id' => $driver->id,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(ShoppingList::STATUS_QUOTED, $shoppingList->fresh()->status);
    }

    public function test_assigned_driver_confirms_delivery_to_complete_transaction(): void
    {
        Role::findOrCreate('delivery-person');

        $driverUser = User::factory()->create();
        $driverUser->assignRole('delivery-person');
        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'name' => 'John Driver',
            'phone' => '+256701111222',
            'email' => $driverUser->email,
            'is_approved' => true,
            'is_available' => true,
        ]);

        $shoppingList = ShoppingList::create([
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'delivery_preference' => 'school',
            'delivery_location' => 'Gayaza High School reception',
            'source' => ShoppingList::SOURCE_CART,
            'items_subtotal' => 120000,
            'delivery_fee' => 15000,
            'estimated_total' => 135000,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'assigned_driver_id' => $driver->id,
        ]);

        $this->actingAs($driverUser)
            ->patch(route('driver.deliveries.confirm', $shoppingList), [
                'delivery_notes' => 'Received by school bursar.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $shoppingList->refresh();

        $this->assertSame(ShoppingList::STATUS_FULFILLED, $shoppingList->status);
        $this->assertSame($driverUser->id, $shoppingList->delivery_confirmed_by);
        $this->assertNotNull($shoppingList->delivery_confirmed_at);
        $this->assertSame('Received by school bursar.', $shoppingList->delivery_notes);
    }

    public function test_driver_cannot_confirm_delivery_before_payment_is_verified(): void
    {
        Role::findOrCreate('delivery-person');

        $driverUser = User::factory()->create();
        $driverUser->assignRole('delivery-person');
        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'name' => 'John Driver',
            'email' => $driverUser->email,
            'is_approved' => true,
            'is_available' => true,
        ]);

        $shoppingList = ShoppingList::create([
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'delivery_preference' => 'school',
            'source' => ShoppingList::SOURCE_CART,
            'items_subtotal' => 120000,
            'delivery_fee' => 15000,
            'estimated_total' => 135000,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PENDING,
            'assigned_driver_id' => $driver->id,
        ]);

        $this->actingAs($driverUser)
            ->patch(route('driver.deliveries.confirm', $shoppingList))
            ->assertSessionHasErrors('delivery');

        $this->assertSame(ShoppingList::STATUS_QUOTED, $shoppingList->fresh()->status);
    }

    public function test_quoted_invoice_redirects_to_flutterwave_checkout(): void
    {
        config(['services.flutterwave.secret_key' => 'FLWSECK_TEST']);

        Http::fake([
            'api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'data' => [
                    'link' => 'https://checkout.flutterwave.com/pay/edukit-test',
                ],
            ]),
        ]);

        $shoppingList = ShoppingList::create([
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'email' => 'norah@example.test',
            'delivery_preference' => 'school',
            'source' => ShoppingList::SOURCE_CART,
            'cart_items' => [
                [
                    'product_id' => 1,
                    'name' => 'School Backpack',
                    'sku' => 'EDK260900001',
                    'unit_price' => 60000,
                    'quantity' => 2,
                    'line_total' => 120000,
                ],
            ],
            'items_subtotal' => 120000,
            'delivery_fee' => 15000,
            'estimated_total' => 135000,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);

        $this->post(route('website.quote.pay', $shoppingList->reference))
            ->assertRedirect('https://checkout.flutterwave.com/pay/edukit-test');

        $shoppingList->refresh();

        $this->assertSame(ShoppingList::PAYMENT_PENDING, $shoppingList->payment_status);
        $this->assertSame('flutterwave', $shoppingList->payment_provider);
        $this->assertStringStartsWith($shoppingList->reference.'-', $shoppingList->payment_reference);

        Http::assertSent(fn ($request): bool => $request['amount'] === 135000
            && $request['currency'] === 'UGX'
            && $request['customer']['email'] === 'norah@example.test');
    }

    public function test_flutterwave_callback_marks_verified_invoice_paid(): void
    {
        config(['services.flutterwave.secret_key' => 'FLWSECK_TEST']);

        $shoppingList = ShoppingList::create([
            'parent_name' => 'Norah A.',
            'phone' => '+256700123456',
            'email' => 'norah@example.test',
            'delivery_preference' => 'school',
            'source' => ShoppingList::SOURCE_CART,
            'items_subtotal' => 120000,
            'delivery_fee' => 15000,
            'estimated_total' => 135000,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PENDING,
            'payment_provider' => 'flutterwave',
            'payment_reference' => 'EDK-260912-ABCDE',
        ]);

        Http::fake([
            'api.flutterwave.com/v3/transactions/12345/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'successful',
                    'tx_ref' => 'EDK-260912-ABCDE',
                    'amount' => 135000,
                    'currency' => 'UGX',
                ],
            ]),
        ]);

        $this->get(route('website.payments.flutterwave.callback', [
            'status' => 'successful',
            'tx_ref' => 'EDK-260912-ABCDE',
            'transaction_id' => '12345',
        ]))
            ->assertRedirect(route('website.quote.show', $shoppingList->reference))
            ->assertSessionHas('status');

        $shoppingList->refresh();

        $this->assertSame(ShoppingList::PAYMENT_PAID, $shoppingList->payment_status);
        $this->assertSame(ShoppingList::STATUS_QUOTED, $shoppingList->status);
        $this->assertNotNull($shoppingList->paid_at);
    }
}
