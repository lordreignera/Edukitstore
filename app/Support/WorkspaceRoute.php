<?php

namespace App\Support;

use App\Models\User;

class WorkspaceRoute
{
    public static function for(?User $user): string
    {
        if (! $user) {
            return 'dashboard';
        }

        if ($user->hasAnyRole(['super-admin', 'admin']) || $user->can('admin.access')) {
            return 'admin.dashboard';
        }

        if ($user->hasRole('delivery-person')) {
            return 'driver.dashboard';
        }

        if ($user->hasRole('supplier')) {
            return 'supplier.dashboard';
        }

        if ($user->hasRole('finance-admin')) {
            return 'finance.dashboard';
        }

        if ($user->hasAnyRole(['school-admin', 'school-receiver'])) {
            return 'school.dashboard';
        }

        if ($user->hasRole('customer')) {
            return 'customer.dashboard';
        }

        return 'dashboard';
    }
}
