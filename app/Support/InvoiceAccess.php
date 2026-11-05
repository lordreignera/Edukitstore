<?php

namespace App\Support;

use App\Models\ShoppingList;

class InvoiceAccess
{
    public static function grant(ShoppingList $invoice): void
    {
        session()->put('invoice_access.'.$invoice->id, true);
    }

    public static function check(ShoppingList $invoice): void
    {
        if ($invoice->customer_user_id !== null) {
            abort_unless((int) auth()->id() === (int) $invoice->customer_user_id, 404);

            return;
        }

        abort_unless((bool) session('invoice_access.'.$invoice->id), 404);
    }
}
