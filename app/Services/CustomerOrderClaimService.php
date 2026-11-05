<?php

namespace App\Services;

use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Support\Str;

class CustomerOrderClaimService
{
    public function claimVerifiedGuestOrders(User $customer): int
    {
        if (! $customer->hasRole('customer') || ! $customer->hasVerifiedEmail()) {
            return 0;
        }

        $email = Str::lower(trim($customer->email));

        if ($email === '') {
            return 0;
        }

        return ShoppingList::query()
            ->whereNull('customer_user_id')
            ->whereNotNull('email')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->update(['customer_user_id' => $customer->id]);
    }
}
