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

    public function test_default_seeded_admin_password_remains_available(): void
    {
        config([
            'edukit.initial_admin_email' => 'superadmin@edukit.test',
            'edukit.initial_admin_password' => 'password',
        ]);
        app(RolesAndSuperAdminSeeder::class)->run();
        $admin = User::where('email', 'superadmin@edukit.test')->firstOrFail();

        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue($admin->must_change_password);
    }
}
