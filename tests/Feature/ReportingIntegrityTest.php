<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
