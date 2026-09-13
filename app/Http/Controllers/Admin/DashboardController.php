<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\School;
use App\Models\ShoppingList;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'pending_suppliers' => Supplier::where('is_approved', false)->count(),
            'approved_suppliers' => Supplier::where('is_approved', true)->count(),
            'pending_shopping_lists' => ShoppingList::where('status', ShoppingList::STATUS_PENDING)->count(),
            'shopping_lists' => ShoppingList::count(),
            'schools' => School::count(),
            'active_schools' => School::where('is_active', true)->count(),
            'pending_drivers' => Driver::where('is_approved', false)->count(),
            'approved_drivers' => Driver::where('is_approved', true)->count(),
        ];

        $latestProducts = Product::with('category')->latest()->take(5)->get();
        $latestSuppliers = Supplier::latest()->take(5)->get();
        $latestShoppingLists = ShoppingList::latest()->take(5)->get();
        $latestDrivers = Driver::latest()->take(5)->get();

        $productCategorySummary = ProductCategory::withCount('products')
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->take(6)
            ->get();

        $shoppingListStatusCounts = ShoppingList::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $shoppingListStatusSummary = collect(ShoppingList::statuses())->map(function ($label, $status) use ($shoppingListStatusCounts) {
            return [
                'status' => $status,
                'label' => $label,
                'count' => (int) ($shoppingListStatusCounts[$status] ?? 0),
            ];
        });

        return view('admin.dashboard', compact(
            'stats',
            'latestProducts',
            'latestSuppliers',
            'latestShoppingLists',
            'latestDrivers',
            'productCategorySummary',
            'shoppingListStatusSummary',
        ));
    }
}
