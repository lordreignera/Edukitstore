<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@edukit.test'],
            [
                'name' => 'EduKit Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->assignRole('super-admin');
    }
}
