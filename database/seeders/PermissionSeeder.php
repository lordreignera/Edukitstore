<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionCatalog::names() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Permission creation clears the cache through model events, but an
        // explicit refresh here also makes a fresh migration deterministic
        // when a persistent cache contains an older permission list.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionCatalog::defaultRolePermissions() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            // Do not overwrite a role's deliberate custom assignment on later
            // seed runs. Defaults are applied only to empty system roles.
            if (! $role->permissions()->exists()) {
                $role->syncPermissions($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
