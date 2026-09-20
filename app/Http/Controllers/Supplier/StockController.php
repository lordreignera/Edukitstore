<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $supplier = auth()->user()->supplier;

        abort_unless($supplier?->is_approved && $supplier->is_active, 403);

        $search = trim((string) $request->query('q'));
        $batches = InventoryBatch::query()
            ->where('supplier_id', $supplier->id)
            ->with('product.category')
            ->when($search !== '', fn ($query) => $query->whereHas('product', fn ($products) => $products
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->latest('received_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('supplier.stock.index', compact('supplier', 'batches', 'search'));
    }
}
