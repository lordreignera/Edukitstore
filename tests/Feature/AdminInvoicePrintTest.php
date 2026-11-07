<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInvoicePrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_workspace_shows_item_photos_and_links_to_print_sheet(): void
    {
        $admin = $this->adminWithInvoiceAccess();
        $product = Product::create([
            'name' => 'Exercise Book Pack',
            'slug' => 'exercise-book-pack',
            'sku' => 'BOOK-PACK-01',
            'price' => 12000,
            'image_path' => 'https://cdn.example.test/exercise-book.jpg',
            'is_active' => true,
        ]);
        $order = $this->order();
        $order->lineItems()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 12000,
            'line_total' => 24000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invoices.show', $order))
            ->assertOk()
            ->assertSee('Check, pack and assign this order')
            ->assertSee('Exercise Book Pack')
            ->assertSee('https://cdn.example.test/exercise-book.jpg')
            ->assertSee(route('admin.invoices.print', $order), false)
            ->assertSee('Print invoice / fulfilment sheet');
    }

    public function test_admin_can_print_invoice_with_compact_branding_customer_and_driver_details(): void
    {
        $admin = $this->adminWithInvoiceAccess();
        $driver = Driver::create([
            'name' => 'Amina Driver',
            'phone' => '+256700111222',
            'is_approved' => true,
            'is_available' => true,
        ]);
        $product = Product::create([
            'name' => 'Class Set Pens',
            'slug' => 'class-set-pens',
            'sku' => 'PEN-SET-02',
            'price' => 8000,
            'image_path' => 'https://cdn.example.test/pens.jpg',
            'is_active' => true,
        ]);
        $order = $this->order([
            'assigned_driver_id' => $driver->id,
            'packed_at' => now(),
            'payment_provider' => 'demo',
        ]);
        $order->lineItems()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 3,
            'unit_price' => 8000,
            'line_total' => 24000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invoices.print', $order))
            ->assertOk()
            ->assertSee('/images/website/EDUKIT_STORE_logo2.png', false)
            ->assertSee('Order invoice &amp; fulfilment', false)
            ->assertSee('Customer Name')
            ->assertSee('+256700000000')
            ->assertSee('Class Set Pens')
            ->assertSee('Amina Driver')
            ->assertSee('+256700111222')
            ->assertSee('Demo payment — no funds collected')
            ->assertSee('@media print', false)
            ->assertSee('Print / Save PDF');
    }

    public function test_print_sheet_reconciles_deliverable_items_with_original_payment_and_refund(): void
    {
        $admin = $this->adminWithInvoiceAccess();
        $order = $this->order([
            'items_subtotal' => 10000,
            'delivery_fee' => 1000,
            'estimated_total' => 11000,
            'packed_at' => now(),
        ]);
        $order->lineItems()->create([
            'product_name' => 'Exercise Book',
            'sku' => 'BOOK-01',
            'quantity' => 1,
            'ordered_quantity' => 2,
            'unavailable_quantity' => 1,
            'unavailable_reason' => 'out_of_stock',
            'unit_price' => 5000,
            'line_total' => 5000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invoices.print', $order))
            ->assertOk()
            ->assertSee('Deliverable items subtotal')
            ->assertSee('UGX 5,000')
            ->assertSee('Original amount paid')
            ->assertSee('UGX 11,000')
            ->assertSee('Manual refund due')
            ->assertSee('Expected amount after refund')
            ->assertSee('UGX 6,000');
    }

    private function adminWithInvoiceAccess(): User
    {
        $role = Role::findOrCreate('super-admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('invoices.view', 'web'));

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function order(array $attributes = []): ShoppingList
    {
        return ShoppingList::create(array_merge([
            'parent_name' => 'Customer Name',
            'phone' => '+256700000000',
            'email' => 'customer@example.test',
            'school_name' => 'Kampala Primary School',
            'learner_name' => 'Learner One',
            'class_level' => 'P5',
            'delivery_preference' => 'delivery',
            'delivery_location' => 'Kampala Central',
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'flutterwave',
            'items_subtotal' => 24000,
            'delivery_fee' => 3000,
            'estimated_total' => 27000,
            'paid_at' => now(),
        ], $attributes));
    }
}
