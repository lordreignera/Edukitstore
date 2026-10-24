<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndSuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBootstrapSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_does_not_reset_an_existing_admin_password(): void
    {
        config([
            'edukit.initial_admin_email' => 'owner@example.test',
            'edukit.initial_admin_password' => 'A-unique-setup-secret-12345',
        ]);
        app(RolesAndSuperAdminSeeder::class)->run();
        $admin = User::where('email', 'owner@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('A-unique-setup-secret-12345', $admin->password));
        $admin->update(['password' => 'User-chosen-new-password-12345']);

        app(RolesAndSuperAdminSeeder::class)->run();

        $this->assertTrue(Hash::check('User-chosen-new-password-12345', $admin->fresh()->password));
    }

    public function test_short_seeded_admin_password_is_rejected(): void
    {
        config([
            'edukit.initial_admin_email' => 'superadmin@edukit.test',
            'edukit.initial_admin_password' => 'password',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('at least 16 characters');

        app(RolesAndSuperAdminSeeder::class)->run();
    }

    public function test_admin_is_not_created_when_bootstrap_credentials_are_not_configured(): void
    {
        config([
            'edukit.initial_admin_email' => null,
            'edukit.initial_admin_password' => null,
        ]);

        app(RolesAndSuperAdminSeeder::class)->run();

        $this->assertDatabaseCount('users', 0);
    }
}
