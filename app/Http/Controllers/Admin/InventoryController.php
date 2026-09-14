<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ShoppingList;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $categoryId = (string) $request->query('category');
        $stockStatus = (string) $request->query('stock_status');

        $products = Product::query()
            ->with('category')
            ->withSum(['inventoryMovements as sold_units' => fn ($query) => $query->where('type', InventoryMovement::TYPE_SALE_PAID)], 'quantity')
            ->withSum(['inventoryMovements as gross_profit' => fn ($query) => $query->where('type', InventoryMovement::TYPE_SALE_PAID)], 'profit')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when($categoryId !== '', fn ($query) => $query->where('product_category_id', $categoryId))
            ->when($stockStatus === 'low_display', fn ($query) => $query->whereColumn('stock_quantity', '<=', 'reorder_level'))
            ->when($stockStatus === 'out_of_display', fn ($query) => $query->where('stock_quantity', 0))
            ->when($stockStatus === 'warehouse_empty', fn ($query) => $query->where('warehouse_stock_quantity', 0))
            ->when($stockStatus === 'ready', fn ($query) => $query->where('stock_quantity', '>', 0))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $invoiceMetrics = $this->invoiceProductMetrics();

        $products->getCollection()->transform(function (Product $product) use ($invoiceMetrics): Product {
            $metrics = $invoiceMetrics[$product->id] ?? ['ordered' => 0, 'paid_pending' => 0, 'in_transit' => 0];

            $product->ordered_units = $metrics['ordered'];
            $product->paid_pending_units = $metrics['paid_pending'];
            $product->in_transit_units = $metrics['in_transit'];

            return $product;
        });

        $allProducts = Product::all(['stock_quantity', 'warehouse_stock_quantity', 'reorder_level', 'cost_price']);
        $stats = [
            'display_units' => $allProducts->sum('stock_quantity'),
            'warehouse_units' => $allProducts->sum('warehouse_stock_quantity'),
            'low_display_products' => $allProducts->filter(fn (Product $product): bool => $product->stock_quantity <= $product->reorder_level)->count(),
            'sold_units' => (int) InventoryMovement::where('type', InventoryMovement::TYPE_SALE_PAID)->sum('quantity'),
            'gross_profit' => (float) InventoryMovement::where('type', InventoryMovement::TYPE_SALE_PAID)->sum('profit'),
        ];

        $recentMovements = InventoryMovement::query()
            ->with('product', 'performer')
            ->latest()
            ->take(8)
            ->get();

        return view('admin.inventory.index', [
            'products' => $products,
            'categories' => ProductCategory::orderBy('name')->get(),
            'movementLabels' => InventoryMovement::labels(),
            'recentMovements' => $recentMovements,
            'stats' => $stats,
            'search' => $search,
            'categoryId' => $categoryId,
            'stockStatus' => $stockStatus,
        ]);
    }

    public function intake(Request $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            '_modal' => ['nullable', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $inventory->recordIntake($product, $data, auth()->id());

        return back()->with('status', 'Stock intake recorded for '.$product->name.'.');
    }

    public function transfer(Request $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            '_modal' => ['nullable', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $inventory->transferToDisplay($product, (int) $data['quantity'], auth()->id(), $data['notes'] ?? null);

        return back()->with('status', $product->name.' moved from warehouse to website/display stock.');
    }

    private function invoiceProductMetrics(): array
    {
        $metrics = [];

        $invoices = ShoppingList::query()
            ->where('source', ShoppingList::SOURCE_CART)
            ->whereNull('delivery_confirmed_at')
            ->whereNotNull('cart_items')
            ->get(['cart_items', 'payment_status', 'assigned_driver_id']);

        foreach ($invoices as $invoice) {
            foreach ($invoice->cart_items ?? [] as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($productId < 1 || $quantity < 1) {
                    continue;
                }

                $metrics[$productId] ??= ['ordered' => 0, 'paid_pending' => 0, 'in_transit' => 0];

                if ($invoice->payment_status === ShoppingList::PAYMENT_PAID && $invoice->assigned_driver_id) {
                    $metrics[$productId]['in_transit'] += $quantity;
                } elseif ($invoice->payment_status === ShoppingList::PAYMENT_PAID) {
                    $metrics[$productId]['paid_pending'] += $quantity;
                } else {
                    $metrics[$productId]['ordered'] += $quantity;
                }
            }
        }

        return $metrics;
    }
}
