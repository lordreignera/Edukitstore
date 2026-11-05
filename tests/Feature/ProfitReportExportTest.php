<?php

namespace Tests\Feature;

use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfitReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_all_profit_rows_matching_the_report_filters(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        for ($index = 1; $index <= 21; $index++) {
            $this->addItem($this->paidOrder(['payment_provider' => 'flutterwave', 'paid_at' => '2026-11-03 09:00:00']), [
                'fulfilment_source' => 'edukit', 'product_name' => "Counter Book {$index}", 'sku' => "EDK-{$index}",
                'quantity' => 2, 'unit_price' => 5000, 'line_total' => 10000,
                'cost_total' => 6000, 'profit_total' => 4000,
            ]);
        }
        $this->addItem($this->paidOrder(['payment_provider' => 'demo', 'paid_at' => '2026-11-03 10:00:00']), [
            'fulfilment_source' => 'edukit', 'product_name' => 'Dry-run Pen', 'sku' => 'EDK-102',
            'quantity' => 1, 'unit_price' => 2000, 'line_total' => 2000,
            'cost_total' => 1000, 'profit_total' => 1000,
        ]);
        $this->addItem($this->paidOrder(['payment_provider' => 'flutterwave', 'paid_at' => '2026-11-03 11:00:00']), [
            'fulfilment_source' => 'supplier', 'product_name' => 'Supplier Ruler', 'sku' => 'SUP-103',
            'quantity' => 1, 'unit_price' => 3000, 'line_total' => 3000,
            'cost_total' => 2000, 'profit_total' => 1000,
        ]);
        $this->addItem($this->paidOrder(['payment_provider' => 'flutterwave', 'paid_at' => '2026-10-31 23:59:00']), [
            'fulfilment_source' => 'edukit', 'product_name' => 'Outside Date Book', 'sku' => 'EDK-104',
            'quantity' => 1, 'unit_price' => 9000, 'line_total' => 9000,
            'cost_total' => 5000, 'profit_total' => 4000,
        ]);

        $filters = [
            'source' => 'edukit',
            'from' => '2026-11-01',
            'to' => '2026-11-30',
            'include_demo' => 1,
        ];

        $this->actingAs($admin)
            ->get(route('admin.reports.profit', $filters))
            ->assertOk()
            ->assertSee('Download filtered CSV')
            ->assertSee(route('admin.reports.profit.export', $filters));

        $response = $this->get(route('admin.reports.profit.export', $filters));

        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('EduKit gross profit (UGX)', $csv);
        $this->assertStringContainsString('Counter Book 21', $csv);
        $this->assertSame(21, substr_count($csv, 'Counter Book'));
        $this->assertStringContainsString('Dry-run Pen', $csv);
        $this->assertStringContainsString('demo (test)', $csv);
        $this->assertStringNotContainsString('Supplier Ruler', $csv);
        $this->assertStringNotContainsString('Outside Date Book', $csv);

        $withoutDemo = $this->get(route('admin.reports.profit.export', [
            'source' => 'edukit', 'from' => '2026-11-01', 'to' => '2026-11-30',
        ]))->streamedContent();
        $this->assertStringNotContainsString('Dry-run Pen', $withoutDemo);
    }

    public function test_finance_csv_export_ignores_demo_payment_filter(): void
    {
        $role = Role::findOrCreate('finance-admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('reports.profit.view', 'web'));
        $financeUser = User::factory()->create();
        $financeUser->assignRole($role);
        $this->addItem($this->paidOrder(['payment_provider' => 'demo', 'paid_at' => '2026-11-03 10:00:00']), [
            'fulfilment_source' => 'edukit', 'product_name' => 'Test-only Product', 'quantity' => 1,
            'unit_price' => 2000, 'line_total' => 2000, 'cost_total' => 1000, 'profit_total' => 1000,
        ]);

        $response = $this->actingAs($financeUser)
            ->get(route('finance.profit.export', ['include_demo' => 1]));

        $response->assertOk()->assertDownload();
        $this->assertStringNotContainsString('Test-only Product', $response->streamedContent());
    }

    private function paidOrder(array $attributes = []): ShoppingList
    {
        return ShoppingList::create($attributes + [
            'parent_name' => 'Export Customer',
            'phone' => '+256700000001',
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'paid_at' => '2026-11-03 08:00:00',
        ]);
    }

    private function addItem(ShoppingList $order, array $attributes): void
    {
        $order->lineItems()->create($attributes);
    }
}
