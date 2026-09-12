<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Everything learners need, managed in one place.')
            ->assertSee('/images/website/edukit-store-logo.png', false);
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
            ->assertSee('Action Centre')
            ->assertSee('Catalogue by Category')
            ->assertSee('Shopping List Workflow')
            ->assertSee('Latest Products')
            ->assertSee('Uganda Primary Atlas')
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

    private function superAdmin(): User
    {
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }
}
