<?php

namespace App\Support;

use Illuminate\Support\Str;

class PermissionCatalog
{
    /**
     * The permissions exposed by the EduKit workspaces.
     *
     * The catalog gives administrators a stable list to assign to roles while
     * still allowing custom permissions to be added from the permissions screen.
     * Route enforcement can be introduced module by module without changing the
     * role-management data model.
     *
     * @return array<string, array<string, string>>
     */
    public static function modules(): array
    {
        return [
            'Administration' => [
                'admin.access' => 'Open the administration workspace',
                'admin.dashboard.view' => 'View the administration dashboard',
            ],
            'User Management' => [
                'users.view' => 'View user accounts',
                'users.create' => 'Create user accounts',
                'users.update' => 'Update user accounts and roles',
                'users.deactivate' => 'Activate or deactivate user accounts',
                'users.reset-password' => 'Send password setup links',
                'roles.view' => 'View roles',
                'roles.create' => 'Create roles',
                'roles.update' => 'Update roles and permissions',
                'roles.delete' => 'Delete custom roles',
                'permissions.view' => 'View permissions',
                'permissions.create' => 'Create permissions',
                'permissions.update' => 'Update permissions',
                'permissions.delete' => 'Delete custom permissions',
            ],
            'Catalogue' => [
                'products.view' => 'View products',
                'products.create' => 'Create products',
                'products.update' => 'Update products',
                'products.delete' => 'Archive products',
                'categories.view' => 'View product categories',
                'categories.create' => 'Create product categories',
                'categories.update' => 'Update product categories',
                'categories.delete' => 'Delete product categories',
            ],
            'Inventory and Finance' => [
                'inventory.view' => 'View inventory',
                'inventory.manage' => 'Manage stock and inventory movements',
                'inventory.import' => 'Import inventory CSV files',
                'inventory.export' => 'Export inventory CSV files',
                'reports.profit.view' => 'View profit reports',
            ],
            'Schools and Operations' => [
                'schools.view' => 'View schools',
                'schools.create' => 'Create schools',
                'schools.update' => 'Update schools and delivery fees',
                'schools.delete' => 'Delete schools',
                'shopping-lists.view' => 'View shopping lists',
                'shopping-lists.review' => 'Review shopping lists',
                'shopping-lists.download' => 'Download shopping list files',
                'invoices.view' => 'View invoices',
                'invoices.update' => 'Update invoices and payment status',
                'invoices.resolve-stock' => 'Resolve paid-stock exceptions',
            ],
            'Partners' => [
                'suppliers.view' => 'View suppliers',
                'suppliers.create' => 'Create suppliers',
                'suppliers.update' => 'Update suppliers',
                'suppliers.approve' => 'Approve suppliers',
                'suppliers.suspend' => 'Suspend or reinstate suppliers',
                'suppliers.download' => 'Download supplier documents',
                'supplier-products.view' => 'View supplier products',
                'supplier-products.approve' => 'Approve supplier products',
                'supplier-products.reject' => 'Reject supplier products',
                'drivers.view' => 'View delivery partners',
                'drivers.create' => 'Create delivery partners',
                'drivers.update' => 'Update delivery partners',
                'drivers.approve' => 'Approve delivery partners',
                'drivers.availability' => 'Manage driver availability',
                'drivers.download' => 'Download driver documents',
            ],
            'Partner Workspaces' => [
                'finance.dashboard.view' => 'Open the finance workspace',
                'supplier.dashboard.view' => 'Open the supplier workspace',
                'supplier.stock.view' => 'View supplier stock',
                'supplier-products.manage' => 'Manage supplier product offers',
                'supplier.earnings.view' => 'View supplier earnings',
                'driver.dashboard.view' => 'Open the driver workspace',
                'driver.deliveries.view' => 'View assigned deliveries',
                'driver.deliveries.update' => 'Update delivery progress',
                'school.dashboard.view' => 'Open the school workspace',
                'parent.dashboard.view' => 'Open the parent workspace',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return collect(self::modules())
            ->flatMap(fn (array $permissions): array => array_keys($permissions))
            ->values()
            ->all();
    }

    public static function label(string $name): string
    {
        foreach (self::modules() as $permissions) {
            if (isset($permissions[$name])) {
                return $permissions[$name];
            }
        }

        return Str::of($name)
            ->replace(['.', '-', '_'], ' ')
            ->title()
            ->toString();
    }

    public static function group(string $name): string
    {
        foreach (self::modules() as $module => $permissions) {
            if (array_key_exists($name, $permissions)) {
                return $module;
            }
        }

        return 'Custom permissions';
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function defaultRolePermissions(): array
    {
        $all = self::names();

        return [
            'super-admin' => $all,
            'admin' => $all,
            'finance-admin' => [
                'finance.dashboard.view',
                'reports.profit.view',
            ],
            'supplier' => [
                'supplier.dashboard.view',
                'supplier.stock.view',
                'supplier-products.manage',
                'supplier.earnings.view',
            ],
            'delivery-person' => [
                'driver.dashboard.view',
                'driver.deliveries.view',
                'driver.deliveries.update',
            ],
            'school-admin' => ['school.dashboard.view'],
            'school-receiver' => ['school.dashboard.view'],
            'parent' => ['parent.dashboard.view'],
        ];
    }
}
