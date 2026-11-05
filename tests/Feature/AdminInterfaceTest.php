<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_the_edukit_authentication_shell(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('Back to shop')
            ->assertDontSee('Everything learners need, managed in one place.')
            ->assertSee('/images/website/EDUKIT_STORE_logo2.png', false);
    }

    public function test_super_admin_dashboard_renders_real_management_sections(): void
    {
        $admin = $this->superAdmin();

        Product::create([
            'name' => 'Uganda Primary Atlas',
            'slug' => 'uganda-primary-atlas',
            'sku' => 'EDK-ATLAS-001',
            'price' => 28000,
            'stock_quantity' => 14,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Performance overview')
            ->assertSee('Orders received')
            ->assertSee('Paid item revenue')
            ->assertSee('Action center')
            ->assertSee('Orders & Fulfilment')
            ->assertSee('Orders received')
            ->assertSee('Platform overview')
            ->assertSee('Recent orders')
            ->assertSee('Search orders, products, schools, suppliers')
            ->assertSee('Orders & Fulfilment')
            ->assertSee('View Website');
    }

    public function test_admin_can_search_the_master_product_list(): void
    {
        $admin = $this->superAdmin();

        Product::create([
            'name' => 'Picfare Counter Book',
            'slug' => 'picfare-counter-book',
            'sku' => 'PIC-001',
            'price' => 5000,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Black School Shoes',
            'slug' => 'black-school-shoes',
            'sku' => 'SHOE-001',
            'price' => 45000,
            'stock_quantity' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['q' => 'Picfare']))
            ->assertOk()
            ->assertSee('Picfare Counter Book')
            ->assertDontSee('Black School Shoes');
    }

    public function test_dashboard_kpi_counts_only_paid_issue_free_orders_waiting_for_driver_assignment(): void
    {
        config([
            'services.webpush.public_key' => null,
            'services.webpush.private_key' => null,
            'services.webpush.subject' => null,
        ]);

        $role = Role::findOrCreate('super-admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('invoices.view', 'web'));
        $admin = User::factory()->create();
        $admin->assignRole($role);

        $createOrder = fn (array $attributes) => ShoppingList::create(array_merge([
            'parent_name' => 'KPI Customer',
            'phone' => '+256700000001',
            'delivery_preference' => 'pickup',
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_PAID,
        ], $attributes));

        $ready = $createOrder([]);
        $exception = $createOrder(['payment_exception' => 'Stock needs review.']);
        $unpaid = $createOrder(['payment_status' => ShoppingList::PAYMENT_UNPAID]);

        $queueUrl = route('admin.invoices.index', [
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'delivery_status' => 'awaiting_driver_assignment',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Awaiting driver assignment')
            ->assertSee(str_replace('&', '&amp;', $queueUrl), false)
            ->assertViewHas('stats', fn ($stats) => $stats['orders_awaiting_driver_assignment'] === 1);

        $this->get($queueUrl)
            ->assertOk()
            ->assertSee($ready->reference)
            ->assertDontSee($exception->reference)
            ->assertDontSee($unpaid->reference);
    }

    public function test_new_list_action_queue_drills_into_pending_and_reviewing_uploads_only(): void
    {
        $admin = $this->superAdmin();
        $createUpload = fn (string $name, string $status) => ShoppingList::create([
            'parent_name' => $name,
            'phone' => '0700000011',
            'source' => ShoppingList::SOURCE_UPLOAD,
            'status' => $status,
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);
        $pending = $createUpload('Pending Upload Customer', ShoppingList::STATUS_PENDING);
        $reviewing = $createUpload('Reviewing Upload Customer', ShoppingList::STATUS_REVIEWING);
        $quoted = $createUpload('Priced Upload Customer', ShoppingList::STATUS_QUOTED);

        $this->actingAs($admin)
            ->get(route('admin.shopping-lists.index', ['review_queue' => 1]))
            ->assertOk()
            ->assertSee('Review queue: uploaded lists pending review or currently being reviewed.')
            ->assertSee($pending->parent_name)
            ->assertSee($reviewing->parent_name)
            ->assertDontSee($quoted->parent_name);
    }

    public function test_global_admin_search_only_returns_modules_the_user_can_access(): void
    {
        $role = Role::findOrCreate('limited-search', 'web');
        $role->givePermissionTo([
            Permission::findOrCreate('admin.access', 'web'),
            Permission::findOrCreate('products.view', 'web'),
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        Product::create([
            'name' => 'Shared Search Term Workbook',
            'slug' => 'shared-search-term-workbook',
            'sku' => 'SEARCH-BOOK-1',
            'price' => 6000,
            'is_active' => true,
        ]);
        $order = ShoppingList::create([
            'parent_name' => 'Shared Search Term Customer',
            'phone' => '0700000099',
            'school_name' => 'Shared Search Term School',
            'status' => ShoppingList::STATUS_PENDING,
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);

        $this->actingAs($user)
            ->get(route('admin.search', ['q' => 'Shared Search Term']))
            ->assertOk()
            ->assertSee('Shared Search Term Workbook')
            ->assertDontSee($order->reference)
            ->assertDontSee('Orders search results');
    }

    private function superAdmin(): User
    {
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }
}
