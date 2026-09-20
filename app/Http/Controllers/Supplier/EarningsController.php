<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\ShoppingListItem;
use Illuminate\Contracts\View\View;

class EarningsController extends Controller
{
    public function index(): View
    {
        $supplier = auth()->user()->supplier;
        abort_unless($supplier?->is_approved && $supplier->is_active, 403);

        $items = ShoppingListItem::query()
            ->where('supplier_id', $supplier->id)
            ->with('shoppingList.school', 'product')
            ->latest()
            ->paginate(15);

        $base = ShoppingListItem::where('supplier_id', $supplier->id);
        $stats = [
            'units_sold' => (clone $base)->sum('quantity'),
            'sales' => (clone $base)->sum('line_total'),
            'payable' => (clone $base)->sum('supplier_payable'),
            'margin' => (clone $base)->sum('profit_total'),
        ];

        return view('supplier.earnings.index', compact('supplier', 'items', 'stats'));
    }
}
