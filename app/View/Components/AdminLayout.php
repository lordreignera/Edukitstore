<?php

namespace App\View\Components;

use App\Models\Driver;
use App\Models\ShoppingList;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdminLayout extends Component
{
    public array $adminNavStats;

    public function __construct()
    {
        $this->adminNavStats = [
            'shopping_lists' => ShoppingList::where('status', ShoppingList::STATUS_PENDING)->count(),
            'invoices' => ShoppingList::where('status', ShoppingList::STATUS_QUOTED)
                ->where('payment_status', '!=', ShoppingList::PAYMENT_PAID)
                ->count(),
            'suppliers' => Supplier::where('is_approved', false)->count(),
            'drivers' => Driver::where('is_approved', false)->count(),
        ];
    }

    public function render(): View
    {
        return view('components.admin-layout');
    }
}
