<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\DriverPayout;
use App\Models\ShoppingList;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_performance_uses_completed_paid_deliveries_and_recorded_payouts(): void
    {
        $admin = $this->adminWith([
            'reports.performance.view',
            'reports.driver-payouts.view',
            'reports.driver-payouts.record',
        ]);
        $driver = $this->driver();

        $paid = $this->completedOrder($driver, [
            'delivery_fee' => 12000,
            'customer_received_at' => '2026-11-02 12:00:00',
            'payment_provider' => 'flutterwave',
        ]);
        $this->completedOrder($driver, [
            'delivery_fee' => 7000,
            'customer_received_at' => '2026-11-03 12:00:00',
            'payment_provider' => 'flutterwave',
        ]);
        $this->completedOrder($driver, [
            'delivery_fee' => 5000,
            'customer_received_at' => '2026-11-03 13:00:00',
            'payment_provider' => 'demo',
        ]);
        $this->completedOrder($driver, [
            'delivery_fee' => 9000,
            'customer_received_at' => '2026-10-30 13:00:00',
            'payment_provider' => 'flutterwave',
        ]);

        DriverPayout::create([
            'shopping_list_id' => $paid->id,
            'driver_id' => $driver->id,
            'amount' => 8000,
            'payment_phone' => $driver->payment_phone,
            'transaction_reference' => 'MOMO-DRIVER-001',
            'paid_at' => '2026-11-04 10:00:00',
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.driver-performance', ['from' => '2026-11-01', 'to' => '2026-11-03']))
            ->assertOk()
            ->assertSee('Driver performance')
            ->assertSee('Norah Delivery')
            ->assertSee('UGX 19,000')
            ->assertSee('UGX 8,000')
            ->assertSee('Include demo payments')
            ->assertViewHas('totals', fn (array $totals) => $totals['completed'] === 2
                && $totals['customer_fees'] === 19000
                && $totals['payouts_recorded'] === 8000
                && $totals['awaiting_payout'] === 1);

        $this->get(route('admin.reports.driver-performance', [
            'from' => '2026-11-01', 'to' => '2026-11-03', 'include_demo' => 1,
        ]))->assertOk()
            ->assertSee('Demo deliveries are included')
            ->assertViewHas('totals', fn (array $totals) => $totals['completed'] === 3
                && $totals['customer_fees'] === 24000
                && $totals['awaiting_payout'] === 1);
    }

    public function test_supplier_performance_groups_paid_supplier_sales_and_settlement_states(): void
    {
        $admin = $this->adminWith(['reports.performance.view']);
        $supplier = Supplier::create(['business_name' => 'Bright Books Ltd', 'is_approved' => true, 'is_active' => true]);

        $reservedOrder = $this->completedOrder(null, [
            'payment_provider' => 'flutterwave',
            'paid_at' => '2026-11-02 09:00:00',
        ]);
        $reservedOrder->lineItems()->create($this->supplierLine($supplier, [
            'quantity' => 3,
            'line_total' => 15000,
            'supplier_payable' => 9000,
            'profit_total' => 6000,
            'settlement_status' => 'reserved',
        ]));

        $earnedOrder = $this->completedOrder(null, [
            'payment_provider' => 'flutterwave',
            'paid_at' => '2026-11-03 09:00:00',
        ]);
        $earnedOrder->lineItems()->create($this->supplierLine($supplier, [
            'quantity' => 2,
            'line_total' => 10000,
            'supplier_payable' => 6000,
            'profit_total' => 4000,
            'settlement_status' => 'earned',
        ]));

        $demoOrder = $this->completedOrder(null, ['payment_provider' => 'demo', 'paid_at' => '2026-11-03 10:00:00']);
        $demoOrder->lineItems()->create($this->supplierLine($supplier, [
            'quantity' => 10,
            'line_total' => 50000,
            'supplier_payable' => 30000,
            'profit_total' => 20000,
        ]));

        $this->actingAs($admin)
            ->get(route('admin.reports.supplier-performance', ['from' => '2026-11-01', 'to' => '2026-11-04']))
            ->assertOk()
            ->assertSee('Supplier performance')
            ->assertSee('Bright Books Ltd')
            ->assertSee('UGX 25,000')
            ->assertSee('UGX 15,000')
            ->assertSee('UGX 9,000')
            ->assertSee('UGX 6,000')
            ->assertDontSee('UGX 75,000')
            ->assertViewHas('totals', fn ($totals) => (int) $totals->paid_orders === 2
                && (int) $totals->units_sold === 5
                && (int) $totals->sales === 25000
                && (int) $totals->supplier_payable === 15000
                && (int) $totals->earned === 6000
                && (int) $totals->reserved === 9000);
    }

    public function test_admin_can_record_one_external_payout_for_an_eligible_completed_delivery(): void
    {
        $admin = $this->adminWith([
            'reports.performance.view',
            'reports.driver-payouts.view',
            'reports.driver-payouts.record',
        ]);
        $driver = $this->driver();
        $order = $this->completedOrder($driver, [
            'delivery_fee' => 12000,
            'customer_received_at' => '2026-11-02 12:00:00',
            'payment_provider' => 'flutterwave',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.driver-payouts'))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee('Mobile Money number:');

        $this->post(route('admin.reports.driver-payouts.store'), [
            'shopping_list_id' => $order->id,
            'amount' => 8500,
            'transaction_reference' => 'EXT-MOMO-54321',
            'paid_at' => '2026-11-04T12:30',
            'notes' => 'Driver delivery payout',
        ])->assertRedirect(route('admin.reports.driver-payouts'))
            ->assertSessionHas('status', 'External Mobile Money payout recorded.');

        $this->assertDatabaseHas('driver_payouts', [
            'shopping_list_id' => $order->id,
            'driver_id' => $driver->id,
            'amount' => 8500,
            'payment_phone' => $driver->payment_phone,
            'transaction_reference' => 'EXT-MOMO-54321',
            'recorded_by' => $admin->id,
            'notes' => 'Driver delivery payout',
        ]);

        $this->post(route('admin.reports.driver-payouts.store'), [
            'shopping_list_id' => $order->id,
            'amount' => 8500,
            'transaction_reference' => 'EXT-MOMO-54322',
            'paid_at' => '2026-11-04T12:30',
        ])->assertSessionHasErrors('shopping_list_id');

        $this->assertDatabaseCount('driver_payouts', 1);
        $this->get(route('admin.reports.driver-payouts'))
            ->assertOk()
            ->assertDontSee('Record payment')
            ->assertSee('EXT-MOMO-54321');
    }

    public function test_demo_unpaid_incomplete_or_exception_orders_cannot_be_paid_out(): void
    {
        $admin = $this->adminWith(['reports.driver-payouts.view', 'reports.driver-payouts.record']);
        $driver = $this->driver();
        $orders = [
            $this->completedOrder($driver, ['payment_provider' => 'demo']),
            $this->completedOrder($driver, ['payment_provider' => 'flutterwave', 'payment_status' => ShoppingList::PAYMENT_UNPAID]),
            ShoppingList::create([
                'parent_name' => 'Not delivered', 'phone' => '+256700000099', 'assigned_driver_id' => $driver->id,
                'payment_status' => ShoppingList::PAYMENT_PAID, 'payment_provider' => 'flutterwave',
            ]),
            $this->completedOrder($driver, ['payment_exception' => 'Amount mismatch']),
        ];

        $this->actingAs($admin);
        foreach ($orders as $index => $order) {
            $this->post(route('admin.reports.driver-payouts.store'), [
                'shopping_list_id' => $order->id,
                'amount' => 1000,
                'transaction_reference' => 'INVALID-PAYOUT-'.$index,
                'paid_at' => '2026-11-04T12:30',
            ])->assertSessionHasErrors('shopping_list_id');
        }

        $this->assertDatabaseCount('driver_payouts', 0);
    }

    public function test_admin_payout_recording_requires_its_dedicated_permission(): void
    {
        $admin = $this->adminWith(['reports.driver-payouts.view']);
        $driver = $this->driver();
        $order = $this->completedOrder($driver);

        $this->actingAs($admin)->get(route('admin.reports.driver-payouts'))
            ->assertOk()
            ->assertDontSee('Record payment')
            ->assertSee('Driver payouts')
            ->assertDontSee('admin/reports/profit')
            ->assertDontSee('admin/reports/driver-performance');
        $this->post(route('admin.reports.driver-payouts.store'), [
            'shopping_list_id' => $order->id,
            'amount' => 1000,
            'transaction_reference' => 'NO-PERMISSION',
            'paid_at' => '2026-11-04T12:30',
        ])->assertForbidden();
    }

    public function test_permission_seeder_adds_new_report_access_to_existing_admin_roles(): void
    {
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo(Permission::findOrCreate('products.view', 'web'));

        app(PermissionSeeder::class)->run();

        $this->assertTrue($adminRole->fresh()->hasPermissionTo('reports.performance.view'));
        $this->assertTrue($adminRole->fresh()->hasPermissionTo('reports.driver-payouts.view'));
        $this->assertTrue($adminRole->fresh()->hasPermissionTo('reports.driver-payouts.record'));
        $this->assertTrue($adminRole->fresh()->hasPermissionTo('products.view'));
    }

    private function adminWith(array $permissions): User
    {
        $role = Role::findOrCreate('admin', 'web');
        $models = collect($permissions)->map(fn (string $name) => Permission::findOrCreate($name, 'web'))->all();
        $role->givePermissionTo($models);

        $admin = User::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }

    private function driver(): Driver
    {
        return Driver::create([
            'name' => 'Norah Delivery',
            'phone' => '+256700000001',
            'payment_phone' => '+256700000002',
            'is_approved' => true,
        ]);
    }

    private function completedOrder(?Driver $driver, array $attributes = []): ShoppingList
    {
        return ShoppingList::create($attributes + [
            'parent_name' => 'Completed customer',
            'phone' => '+256700000010',
            'assigned_driver_id' => $driver?->id,
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'flutterwave',
            'status' => ShoppingList::STATUS_FULFILLED,
            'delivery_fee' => 5000,
            'customer_received_at' => '2026-11-03 12:00:00',
        ]);
    }

    private function supplierLine(Supplier $supplier, array $attributes = []): array
    {
        return $attributes + [
            'supplier_id' => $supplier->id,
            'fulfilment_source' => 'supplier',
            'product_name' => 'Exercise Book',
            'sku' => 'SUP-BOOK-1',
            'quantity' => 1,
            'unit_cost' => 3000,
            'unit_price' => 5000,
            'line_total' => 5000,
            'cost_total' => 3000,
            'supplier_payable' => 3000,
            'profit_total' => 2000,
            'settlement_status' => 'reserved',
        ];
    }
}
