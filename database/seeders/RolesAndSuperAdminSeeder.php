<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RolesAndSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'super-admin',
            'admin',
            'finance-admin',
            'supplier',
            'driver',
            'delivery-person',
            'school-admin',
            'school-receiver',
            'parent',
        ] as $role) {
            Role::findOrCreate($role);
        }

        $email = config('edukit.initial_admin_email');
        $password = config('edukit.initial_admin_password');
        if (! $email || ! $password) {
            return;
        }
        if ($password !== 'password' && strlen($password) < 16) {
            throw new \RuntimeException('EDUKIT_INITIAL_ADMIN_PASSWORD must contain at least 16 characters.');
        }

        $superAdmin = User::firstOrCreate(
            ['email' => Str::lower($email)],
            [
                'name' => 'EduKit Super Admin',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        $superAdmin->assignRole('super-admin');
    }
}
