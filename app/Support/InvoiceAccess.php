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
        abort_unless((bool) session('invoice_access.'.$invoice->id), 404);
    }
}
