<?php

namespace App\View\Components;

use App\Models\Driver;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdminLayout extends Component
{
    public array $adminNavStats;

    public int $unreadNotificationCount;

    public function __construct()
    {
        $user = auth()->user();

        $this->adminNavStats = [
            'shopping_lists' => ShoppingList::where('status', ShoppingList::STATUS_PENDING)->count(),
            'orders' => ShoppingList::whereNotIn('status', [
                ShoppingList::STATUS_FULFILLED,
                ShoppingList::STATUS_CANCELLED,
                ShoppingList::STATUS_REJECTED,
            ])->count(),
            'suppliers' => Supplier::where('is_approved', false)->count(),
            'drivers' => Driver::where('is_approved', false)->count(),
            'low_stock' => Product::whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
        ];
        $this->unreadNotificationCount = $user?->unreadNotifications()->count() ?? 0;
    }

    public function render(): View
    {
        return view('components.admin-layout');
    }
}
