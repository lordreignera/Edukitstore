<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeliveryFeeReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_collected_fees_for_paid_completed_orders_by_driver(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $driver = Driver::create([
            'name' => 'Norah Delivery',
            'phone' => '+256700000001',
            'payment_phone' => '+256700000002',
            'is_approved' => true,
        ]);

        $this->completedOrder([
            'assigned_driver_id' => $driver->id,
            'delivery_fee' => 12000,
            'customer_received_at' => '2026-11-02 12:00:00',
            'payment_provider' => 'flutterwave',
        ]);
        $this->completedOrder([
            'delivery_preference' => 'pickup',
            'delivery_fee' => 0,
            'delivery_confirmed_at' => '2026-11-02 13:00:00',
            'payment_provider' => 'flutterwave',
        ]);
        $this->completedOrder([
            'assigned_driver_id' => $driver->id,
            'delivery_fee' => 7000,
            'customer_received_at' => '2026-11-03 12:00:00',
            'payment_provider' => 'demo',
        ]);
        $this->completedOrder([
            'assigned_driver_id' => $driver->id,
            'delivery_fee' => 5000,
            'customer_received_at' => '2026-10-31 12:00:00',
            'payment_provider' => 'flutterwave',
        ]);
        ShoppingList::create([
            'parent_name' => 'Not yet delivered',
            'phone' => '+256700000099',
            'delivery_fee' => 9000,
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'flutterwave',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.delivery-fees', ['from' => '2026-11-01', 'to' => '2026-11-02']))
            ->assertOk()
            ->assertSee('Delivery Fees')
            ->assertSee('Fees by driver')
            ->assertSee('Customer fees collected')
            ->assertSee('UGX 12,000')
            ->assertSee('Norah Delivery')
            ->assertSee('+256700000002')
            ->assertSee('No driver assigned')
            ->assertDontSee('Test payment')
            ->assertViewHas('totals', fn (array $totals) => $totals['completed_orders'] === 2
                && (int) $totals['fees_collected'] === 12000
                && $totals['orders_without_driver'] === 1);

        $this->get(route('admin.reports.delivery-fees', [
            'from' => '2026-11-01', 'to' => '2026-11-03', 'include_demo' => 1,
        ]))->assertOk()
            ->assertSee('Dry-run view: demo payments are included')
            ->assertSee('Test payment')
            ->assertViewHas('totals', fn (array $totals) => $totals['completed_orders'] === 3
                && (int) $totals['fees_collected'] === 19000);
    }

    public function test_finance_workspace_can_view_fee_report_but_never_demo_payments(): void
    {
        $role = Role::findOrCreate('finance-admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('reports.profit.view', 'web'));
        $financeUser = User::factory()->create();
        $financeUser->assignRole($role);

        $this->completedOrder([
            'delivery_fee' => 12000,
            'customer_received_at' => '2026-11-02 12:00:00',
            'payment_provider' => 'demo',
        ]);

        $this->actingAs($financeUser)
            ->get(route('finance.delivery-fees', ['include_demo' => 1]))
            ->assertOk()
            ->assertSee('Completed-order fees')
            ->assertSee('Delivery fees')
            ->assertDontSee('Include demo/test payments')
            ->assertViewHas('totals', fn (array $totals) => $totals['completed_orders'] === 0
                && (int) $totals['fees_collected'] === 0);
    }

    private function completedOrder(array $attributes): ShoppingList
    {
        return ShoppingList::create(array_merge([
            'parent_name' => 'Completed order customer',
            'phone' => '+256700000010',
            'status' => ShoppingList::STATUS_FULFILLED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
        ], $attributes));
    }
}
