<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_report_uses_payment_date_instead_of_line_creation_date(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(['payment_status' => ShoppingList::PAYMENT_PAID, 'paid_at' => '2026-10-01 10:00:00']);
        $item = $invoice->lineItems()->create([
            'fulfilment_source' => 'edukit', 'product_name' => 'Book', 'quantity' => 1,
            'unit_price' => 5000, 'line_total' => 5000, 'cost_total' => 3000, 'profit_total' => 2000,
        ]);
        $item->forceFill(['created_at' => '2026-09-01 10:00:00'])->save();

        $this->actingAs($admin)->get(route('admin.reports.profit', [
            'from' => '2026-10-01', 'to' => '2026-10-01',
        ]))->assertOk()->assertViewHas('summary', fn ($summary) => (int) $summary->get('edukit')?->revenue === 5000);
    }

    public function test_inventory_demand_uses_edukit_invoice_lines_including_uploaded_lists(): void
    {
        $admin = $this->admin();
        $product = Product::create([
            'name' => 'Counter Book', 'slug' => 'counter-book-report', 'sku' => 'EDK-REPORT-1',
            'price' => 5000, 'is_active' => true,
        ]);
        $uploaded = $this->invoice(['source' => ShoppingList::SOURCE_UPLOAD]);
        $uploaded->lineItems()->create([
            'product_id' => $product->id, 'fulfilment_source' => 'edukit',
            'product_name' => $product->name, 'quantity' => 2, 'unit_price' => 5000, 'line_total' => 10000,
        ]);
        $supplierOrder = $this->invoice();
        $supplierOrder->lineItems()->create([
            'product_id' => $product->id, 'fulfilment_source' => 'supplier',
            'product_name' => $product->name, 'quantity' => 5, 'unit_price' => 5000, 'line_total' => 25000,
        ]);
        $cancelled = $this->invoice(['status' => ShoppingList::STATUS_CANCELLED]);
        $cancelled->lineItems()->create([
            'product_id' => $product->id, 'fulfilment_source' => 'edukit',
            'product_name' => $product->name, 'quantity' => 10, 'unit_price' => 5000, 'line_total' => 50000,
        ]);

        $this->actingAs($admin)->get(route('admin.inventory.index'))->assertOk()
            ->assertViewHas('products', function ($products) use ($product): bool {
                $reported = $products->getCollection()->firstWhere('id', $product->id);

                return $reported && $reported->ordered_units === 2
                    && $reported->paid_pending_units === 0
                    && $reported->in_transit_units === 0;
            });
    }

    public function test_demo_paid_orders_do_not_enter_financial_totals(): void
    {
        $admin = $this->admin();
        $demo = $this->invoice([
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'demo',
            'paid_at' => now(),
        ]);
        $demo->lineItems()->create([
            'fulfilment_source' => 'edukit', 'product_name' => 'Demo shoes',
            'quantity' => 1, 'unit_price' => 45000, 'line_total' => 45000,
            'cost_total' => 30000, 'profit_total' => 15000,
        ]);

        $this->actingAs($admin)->get(route('admin.reports.profit'))
            ->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary->isEmpty());
    }

    public function test_admin_can_include_demo_profit_for_dry_run_without_adding_it_to_finance_totals(): void
    {
        $admin = $this->admin();
        $demo = $this->invoice([
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'demo',
            'paid_at' => now(),
        ]);
        $demo->lineItems()->create([
            'fulfilment_source' => 'edukit', 'product_name' => 'Dry-run school shoes',
            'quantity' => 1, 'unit_price' => 45000, 'line_total' => 45000,
            'cost_total' => 30000, 'profit_total' => 15000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.profit', ['include_demo' => 1]))
            ->assertOk()
            ->assertSee('Dry-run view: demo payments are included')
            ->assertViewHas('summary', fn ($summary) => (int) $summary->get('edukit')?->profit === 15000);

        $financeRole = Role::findOrCreate('finance-admin', 'web');
        $financeRole->givePermissionTo(Permission::findOrCreate('finance.dashboard.view', 'web'));
        $financeUser = User::factory()->create();
        $financeUser->assignRole($financeRole);

        $this->actingAs($financeUser)
            ->get(route('finance.dashboard', ['include_demo' => 1]))
            ->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary->isEmpty());
    }

    public function test_paid_order_profit_is_visible_in_the_admin_order_queue_and_detail(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice([
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'demo',
            'paid_at' => now(),
            'estimated_total' => 45000,
        ]);
        $invoice->lineItems()->create([
            'fulfilment_source' => 'edukit', 'product_name' => 'Dry-run school shoes',
            'quantity' => 1, 'unit_price' => 45000, 'line_total' => 45000,
            'cost_total' => 30000, 'profit_total' => 15000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invoices.index'))
            ->assertOk()
            ->assertSee('EduKit profit')
            ->assertSee('Test profit')
            ->assertSee('UGX 15,000');

        $this->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Test profit')
            ->assertSee('UGX 15,000')
            ->assertSee('no funds were collected');
    }

    public function test_admin_dashboard_filters_order_and_payment_performance_by_date_and_demo_choice(): void
    {
        $admin = $this->admin();
        $paidOrder = $this->invoice([
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'manual',
            'paid_at' => '2026-11-03 09:30:00',
        ]);
        $paidOrder->lineItems()->create([
            'fulfilment_source' => 'edukit', 'product_name' => 'Paid notebook',
            'quantity' => 2, 'unit_price' => 5000, 'line_total' => 10000,
            'cost_total' => 6000, 'profit_total' => 4000,
        ]);

        $demoOrder = $this->invoice([
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'demo',
            'paid_at' => '2026-11-04 15:00:00',
        ]);
        $demoOrder->lineItems()->create([
            'fulfilment_source' => 'edukit', 'product_name' => 'Demo pen',
            'quantity' => 1, 'unit_price' => 2000, 'line_total' => 2000,
            'cost_total' => 1000, 'profit_total' => 1000,
        ]);

        $outsideRangeOrder = $this->invoice([
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'manual',
            'paid_at' => '2026-10-31 23:59:59',
        ]);
        $outsideRangeOrder->lineItems()->create([
            'fulfilment_source' => 'edukit', 'product_name' => 'Earlier book',
            'quantity' => 1, 'unit_price' => 9000, 'line_total' => 9000,
            'cost_total' => 5000, 'profit_total' => 4000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', [
                'period' => 'custom', 'from' => '2026-11-01', 'to' => '2026-11-05',
            ]))
            ->assertOk()
            ->assertSee('Performance overview')
            ->assertViewHas('performance', fn ($performance) => $performance['orders_received'] === 3
                && $performance['paid_orders'] === 1
                && (int) $performance['item_revenue'] === 10000
                && (int) $performance['gross_profit'] === 4000)
            ->assertViewHas('trend', function ($trend): bool {
                $paidDay = collect($trend['points'])->firstWhere('key', '2026-11-03');

                return $trend['granularity'] === 'day'
                    && $paidDay['orders'] === 0
                    && (int) $paidDay['revenue'] === 10000;
            });

        $this->get(route('admin.dashboard', [
            'period' => 'custom', 'from' => '2026-11-01', 'to' => '2026-11-05', 'include_demo' => 1,
        ]))->assertOk()
            ->assertSee('Demo/test payments included')
            ->assertViewHas('performance', fn ($performance) => $performance['paid_orders'] === 2
                && (int) $performance['item_revenue'] === 12000
                && (int) $performance['gross_profit'] === 5000)
            ->assertViewHas('trend', function ($trend): bool {
                $demoDay = collect($trend['points'])->firstWhere('key', '2026-11-04');

                return $demoDay !== null && (int) $demoDay['revenue'] === 2000;
            });
    }

    public function test_dashboard_kpi_date_drilldowns_preserve_date_basis_and_demo_choice(): void
    {
        $admin = $this->admin();
        $realOrder = $this->invoice([
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'manual',
            'paid_at' => '2026-11-03 09:30:00',
        ]);
        $demoOrder = $this->invoice([
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'demo',
            'paid_at' => '2026-11-04 15:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invoices.index', [
                'payment_status' => ShoppingList::PAYMENT_PAID,
                'date_field' => 'paid',
                'from' => '2026-11-01',
                'to' => '2026-11-05',
                'exclude_demo' => 1,
            ]))
            ->assertOk()
            ->assertSee($realOrder->reference)
            ->assertDontSee($demoOrder->reference)
            ->assertSee('Payment date')
            ->assertSee('Exclude demo payments');

        $this->get(route('admin.invoices.index', [
            'date_field' => 'created',
            'from' => '2026-11-01',
            'to' => '2026-11-05',
        ]))
            ->assertOk()
            ->assertSee($realOrder->reference)
            ->assertSee($demoOrder->reference);
    }

    private function admin(): User
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }

    private function invoice(array $attributes = []): ShoppingList
    {
        return ShoppingList::create($attributes + [
            'parent_name' => 'Parent', 'phone' => '0700000000',
            'source' => ShoppingList::SOURCE_CART,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);
    }
}
