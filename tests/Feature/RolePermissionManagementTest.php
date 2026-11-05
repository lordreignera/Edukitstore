<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_permission_role_and_multi_role_user(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('super-admin', 'web');
        Role::findOrCreate('parent', 'web');
        app(PermissionSeeder::class)->run();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('User management')
            ->assertSee('Roles')
            ->assertSee('Permissions');

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Roles')
            ->assertSee('super-admin');

        $this->actingAs($admin)
            ->post(route('admin.permissions.store'), ['name' => 'warehouse.audit'])
            ->assertRedirect(route('admin.permissions.index'));

        $this->assertDatabaseHas('permissions', ['name' => 'warehouse.audit', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), [
                'name' => 'warehouse-manager',
                'permissions' => ['admin.access', 'inventory.view', 'warehouse.audit'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('warehouse-manager', 'web');
        $this->assertTrue($role->hasPermissionTo('warehouse.audit'));

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Warehouse Manager',
                'email' => 'warehouse.manager@example.com',
                'roles' => ['warehouse-manager', 'school-receiver'],
                'password' => 'StarterPass123!',
                'password_confirmation' => 'StarterPass123!',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'warehouse.manager@example.com')->firstOrFail();
        $this->assertTrue($user->hasAllRoles(['warehouse-manager', 'school-receiver']));
    }

    public function test_admin_can_only_use_permissions_assigned_to_the_admin_role(): void
    {
        $admin = User::factory()->create();
        $role = Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('users.view', 'web');
        $admin->assignRole($role);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $role->givePermissionTo('users.view');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_private_navigation_no_longer_hides_the_document_on_normal_navigation(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("event.persisted", $script);
        $this->assertStringNotContainsString("addEventListener('pagehide'", $script);
    }
}
