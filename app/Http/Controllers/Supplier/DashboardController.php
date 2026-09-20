<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\ShoppingListItem;
use App\Models\SupplierOffer;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $supplier = auth()->user()->supplier;

        abort_unless($supplier?->is_approved && $supplier->is_active, 403);

        $batches = InventoryBatch::query()
            ->where('supplier_id', $supplier->id);

        $stats = [
            'products' => $supplier->offers()->where('status', SupplierOffer::STATUS_APPROVED)->count(),
            'units_supplied' => $supplier->offers()->where('status', SupplierOffer::STATUS_APPROVED)->sum('quantity_submitted'),
            'units_remaining' => $supplier->offers()->where('status', SupplierOffer::STATUS_APPROVED)->sum('quantity_available'),
            'purchase_value' => ShoppingListItem::where('supplier_id', $supplier->id)->sum('supplier_payable'),
        ];

        $recentBatches = (clone $batches)
            ->with('product.category')
            ->latest('received_at')
            ->latest('id')
            ->take(8)
            ->get();

        $suppliedProducts = $supplier->offers()
            ->where('status', SupplierOffer::STATUS_APPROVED)
            ->with('product.category')
            ->latest('approved_at')
            ->take(6)
            ->get();

        return view('supplier.dashboard', compact('supplier', 'stats', 'recentBatches', 'suppliedProducts'));
    }
}
