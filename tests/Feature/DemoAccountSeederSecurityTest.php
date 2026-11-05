<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndSuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccountSeederSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_does_not_reset_a_changed_demo_password(): void
    {
        config(['edukit.seed_demo_accounts' => true]);

        app(RolesAndSuperAdminSeeder::class)->run();
        $admin = User::where('email', 'superadmin@edukit.test')->firstOrFail();
        $admin->update([
            'password' => 'User-chosen-new-password-12345',
            'must_change_password' => false,
        ]);

        app(RolesAndSuperAdminSeeder::class)->run();

        $this->assertTrue(Hash::check('User-chosen-new-password-12345', $admin->fresh()->password));
        $this->assertFalse($admin->fresh()->must_change_password);
    }

    public function test_legacy_bootstrap_settings_do_not_create_an_extra_account(): void
    {
        config([
            'edukit.seed_demo_accounts' => true,
            'edukit.initial_admin_email' => 'owner@example.test',
            'edukit.initial_admin_password' => 'A-unique-setup-secret-12345',
        ]);

        app(RolesAndSuperAdminSeeder::class)->run();

        $this->assertDatabaseCount('users', 7);
        $this->assertDatabaseMissing('users', ['email' => 'owner@example.test']);
    }

    public function test_demo_accounts_require_local_opt_in(): void
    {
        config(['edukit.seed_demo_accounts' => false]);

        app(RolesAndSuperAdminSeeder::class)->run();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_demo_credentials_cannot_sign_in_outside_local_or_testing_even_if_database_was_copied(): void
    {
        config(['edukit.seed_demo_accounts' => true]);
        app(RolesAndSuperAdminSeeder::class)->run();

        $this->app['env'] = 'production';
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->post('/login', [
            'email' => 'superadmin@edukit.test',
            'password' => 'Edukit@Super2026!Secure',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $demo = User::where('email', 'superadmin@edukit.test')->firstOrFail();
        $response = $this->actingAs($demo)->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->app['auth']->forgetGuards();
        $this->assertGuest();
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_demo_credentials_can_sign_in_online_when_explicitly_enabled(): void
    {
        config([
            'edukit.seed_demo_accounts' => true,
            'edukit.allow_demo_accounts_online' => true,
        ]);
        $this->app['env'] = 'production';
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        app(RolesAndSuperAdminSeeder::class)->run();

        $this->post('/login', [
            'email' => 'superadmin@edukit.test',
            'password' => 'Edukit@Super2026!Secure',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->get(route('admin.dashboard'))->assertOk();
    }
}
