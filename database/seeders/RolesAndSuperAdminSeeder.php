<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Supplier;
use App\Models\User;
use App\Support\DemoAccountAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndSuperAdminSeeder extends Seeder
{
    /**
     * Seed the optional shared accounts for each EduKit workspace.
     *
     * Online use requires an explicit environment opt-in. Users are reminded
     * to change their temporary password after login.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'super-admin',
            'admin',
            'finance-admin',
            'supplier',
            // Kept for compatibility with older records; new driver accounts
            // use the delivery-person role required by the driver routes.
            'driver',
            'delivery-person',
            'school-admin',
            'school-receiver',
            'customer',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->call(PermissionSeeder::class);

        // Demo accounts require an explicit opt-in. Online use additionally
        // requires EDUKIT_ALLOW_DEMO_ACCOUNTS_ONLINE=true.
        $accounts = DemoAccountAccess::shouldSeed() ? [
            [
                'name' => 'EduKit Super Admin',
                'email' => 'superadmin@edukit.test',
                'password' => 'Edukit@Super2026!Secure',
                'role' => 'super-admin',
            ],
            [
                'name' => 'EduKit Administrator',
                'email' => 'admin@edukit.test',
                'password' => 'Edukit@Admin2026!Secure',
                'role' => 'admin',
            ],
            [
                'name' => 'Finance Administrator',
                'email' => 'finance@edukit.test',
                'password' => 'Edukit@Finance2026!Secure',
                'role' => 'finance-admin',
            ],
            [
                'name' => 'EduKit Supplier',
                'email' => 'supplier@edukit.test',
                'password' => 'Edukit@Supplier2026!Secure',
                'role' => 'supplier',
                'profile' => 'supplier',
            ],
            [
                'name' => 'EduKit Driver',
                'email' => 'driver@edukit.test',
                'password' => 'Edukit@Driver2026!Secure',
                // This is the role used by routes/web.php for the driver workspace.
                'role' => 'delivery-person',
                'profile' => 'driver',
            ],
            [
                'name' => 'School Administrator',
                'email' => 'schooladmin@edukit.test',
                'password' => 'Edukit@School2026!Secure',
                'role' => 'school-admin',
            ],
            [
                'name' => 'School Receiver',
                'email' => 'receiver@edukit.test',
                'password' => 'Edukit@Receiver2026!Secure',
                'role' => 'school-receiver',
            ],
        ] : [];
        DB::transaction(function () use ($accounts): void {
            foreach ($accounts as $account) {
                $user = User::firstOrNew([
                    'email' => Str::lower($account['email']),
                ]);

                $isNew = ! $user->exists;
                $user->fill([
                    'name' => $account['name'],
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'is_active' => true,
                ]);

                // Existing seeded users still on a temporary password receive
                // the documented credential. A password already changed by a
                // user is preserved on later seed runs.
                if ($isNew || $user->must_change_password) {
                    $user->password = Hash::make($account['password']);
                    $user->must_change_password = true;
                }

                $user->save();
                $user->syncRoles([$account['role']]);

                if (($account['profile'] ?? null) === 'supplier') {
                    $this->seedSupplierProfile($user);
                }

                if (($account['profile'] ?? null) === 'driver') {
                    $this->seedDriverProfile($user);
                }

                $this->command?->info("Seeded {$account['role']}: {$account['email']}");
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command?->info('EduKit roles and demo accounts seeded successfully.');
    }

    private function seedSupplierProfile(User $user): void
    {
        Supplier::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => 'EduKit Demo Supplier',
                'contact_person' => $user->name,
                'phone' => '0700000000',
                'email' => $user->email,
                'district' => 'Kampala',
                'product_categories' => 'Books, stationery and school bags',
                'supply_capacity' => 'Demo account',
                'source' => 'seed',
                'is_approved' => true,
                'is_active' => true,
                'approved_at' => now(),
            ]
        );
    }

    private function seedDriverProfile(User $user): void
    {
        Driver::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'phone' => '0700000001',
                'email' => $user->email,
                'district' => 'Kampala',
                'vehicle_type' => 'Motorcycle',
                'vehicle_registration' => 'DEMO-001',
                'source' => 'seed',
                'is_approved' => true,
                'is_available' => true,
                'approved_at' => now(),
            ]
        );
    }
}
