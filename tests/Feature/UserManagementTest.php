<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_a_role_account_with_temporary_password(): void
    {
        Notification::fake();
        $admin = $this->admin();
        Role::findOrCreate('school-admin');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Users &amp; Roles', false)
            ->assertSee('Add user');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Kampala School Administrator',
            'email' => 'school.admin@example.com',
            'role' => 'school-admin',
            'password' => 'StarterPass123!',
            'password_confirmation' => 'StarterPass123!',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'school.admin@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('school-admin'));
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('StarterPass123!', $user->password));
        $this->assertSame($admin->id, $user->created_by);
        Notification::assertNotSentTo($user, ResetPassword::class);

        $this->flushSession();
        $this->actingAs($user);

        $this->get('/dashboard')->assertRedirect(route('profile.show'));
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_generic_user_creation_cannot_bypass_partner_approval(): void
    {
        $admin = $this->admin();
        Role::findOrCreate('supplier');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Unverified Supplier',
            'email' => 'unverified.supplier@example.com',
            'role' => 'supplier',
            'password' => 'StarterPass123!',
            'password_confirmation' => 'StarterPass123!',
            'is_active' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'unverified.supplier@example.com']);
    }

    public function test_regular_admin_cannot_manage_privileged_accounts(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('finance-admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $financeAdmin = User::factory()->create();
        $financeAdmin->assignRole('finance-admin');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $financeAdmin))
            ->assertForbidden();
    }

    public function test_approving_supplier_creates_and_links_a_supplier_account(): void
    {
        Notification::fake();
        $admin = $this->admin();
        Role::findOrCreate('supplier');
        $supplier = Supplier::create([
            'business_name' => 'Kampala Scholastic Supplies',
            'contact_person' => 'Sarah Nakato',
            'email' => 'supplier@example.com',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->patch(route('admin.suppliers.approve', $supplier))->assertRedirect();

        $supplier->refresh();
        $this->assertTrue($supplier->is_approved);
        $this->assertNotNull($supplier->user_id);
        $this->assertTrue($supplier->user->hasRole('supplier'));
        Notification::assertSentTo($supplier->user, ResetPassword::class);

        $this->actingAs($admin)->patch(route('admin.suppliers.suspend', $supplier))->assertRedirect();
        $this->assertFalse($supplier->fresh()->is_active);
        $this->assertFalse($supplier->user->fresh()->is_active);

        $this->actingAs($admin)->patch(route('admin.suppliers.reinstate', $supplier))->assertRedirect();
        $this->assertTrue($supplier->fresh()->is_active);
        $this->assertTrue($supplier->user->fresh()->is_active);
    }

    public function test_partner_approval_cannot_take_over_an_existing_account(): void
    {
        $admin = $this->admin();
        Role::findOrCreate('supplier');
        $supplier = Supplier::create([
            'business_name' => 'Conflicting Supplier',
            'email' => $admin->email,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.suppliers.approve', $supplier))
            ->assertSessionHasErrors('email');

        $this->assertTrue($admin->fresh()->hasRole('super-admin'));
        $this->assertNull($supplier->fresh()->user_id);
    }

    public function test_approving_delivery_partner_creates_and_links_the_correct_account(): void
    {
        Notification::fake();
        $admin = $this->admin();
        Role::findOrCreate('delivery-person');
        $driver = Driver::create([
            'name' => 'John Driver',
            'email' => 'driver@example.com',
            'is_approved' => false,
            'is_available' => false,
        ]);

        $this->actingAs($admin)->patch(route('admin.drivers.approve', $driver))->assertRedirect();

        $driver->refresh();
        $this->assertTrue($driver->is_approved);
        $this->assertTrue($driver->is_available);
        $this->assertTrue($driver->user->hasRole('delivery-person'));
        Notification::assertSentTo($driver->user, ResetPassword::class);

        $this->actingAs($admin)->patch(route('admin.drivers.unavailable', $driver))->assertRedirect();
        $this->assertFalse($driver->fresh()->is_available);

        $this->actingAs($admin)->patch(route('admin.drivers.available', $driver))->assertRedirect();
        $this->assertTrue($driver->fresh()->is_available);

        $this->actingAs($admin)->patch(route('admin.users.status', $driver->user))->assertRedirect();
        $this->assertFalse($driver->user->fresh()->is_active);
        $this->assertFalse($driver->fresh()->is_available);
    }

    private function admin(): User
    {
        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }
}
