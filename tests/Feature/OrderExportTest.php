<?php

namespace Tests\Feature;

use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_all_orders_matching_the_visible_filters(): void
    {
        $role = Role::findOrCreate('super-admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('invoices.view', 'web'));
        $admin = User::factory()->create();
        $admin->assignRole($role);

        $matchingOrder = $this->order([
            'reference' => 'EDK-EXPORT-MATCH',
            'parent_name' => 'Norah Export Customer',
            'email' => 'norah@example.test',
            'payment_provider' => 'flutterwave',
            'paid_at' => '2026-11-03 09:00:00',
            'delivery_fee' => 5000,
            'estimated_total' => 25000,
            'items_subtotal' => 20000,
        ]);
        $matchingOrder->lineItems()->create([
            'product_name' => 'Exercise book',
            'sku' => 'BOOK-1',
            'quantity' => 2,
            'unit_price' => 10000,
            'line_total' => 20000,
            'cost_total' => 14000,
            'profit_total' => 6000,
        ]);

        $this->order([
            'reference' => 'EDK-EXPORT-DEMO',
            'payment_provider' => 'demo',
            'paid_at' => '2026-11-03 10:00:00',
        ]);
        $this->order([
            'reference' => 'EDK-EXPORT-OLD',
            'payment_provider' => 'flutterwave',
            'paid_at' => '2026-10-31 23:59:00',
        ]);

        $filters = [
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'source' => ShoppingList::SOURCE_CART,
            'delivery_status' => 'awaiting_driver_assignment',
            'date_field' => 'paid',
            'from' => '2026-11-01',
            'to' => '2026-11-30',
            'exclude_demo' => 1,
        ];

        $this->actingAs($admin)
            ->get(route('admin.invoices.index', $filters))
            ->assertOk()
            ->assertSee('Export filtered orders CSV')
            ->assertSee('admin/invoices/export?')
            ->assertSee('delivery_status=awaiting_driver_assignment');

        $response = $this->get(route('admin.invoices.export', $filters));

        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('EduKit gross profit (UGX)', $csv);
        $this->assertStringContainsString('EDK-EXPORT-MATCH', $csv);
        $this->assertStringContainsString('Norah Export Customer', $csv);
        $this->assertStringContainsString('norah@example.test', $csv);
        $this->assertStringContainsString('6000', $csv);
        $this->assertStringNotContainsString('EDK-EXPORT-DEMO', $csv);
        $this->assertStringNotContainsString('EDK-EXPORT-OLD', $csv);
    }

    public function test_order_export_requires_order_view_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($user)
            ->get(route('admin.invoices.export'))
            ->assertForbidden();
    }

    private function order(array $attributes = []): ShoppingList
    {
        return ShoppingList::create($attributes + [
            'parent_name' => 'Export Customer',
            'phone' => '+256700000001',
            'source' => ShoppingList::SOURCE_CART,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'paid_at' => '2026-11-03 08:00:00',
        ]);
    }
}
