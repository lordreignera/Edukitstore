<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ShoppingList;
use App\Services\InventoryImportService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function index(Request $request, InventoryService $inventory): View
    {
        $search = trim((string) $request->query('q'));
        $categoryId = (string) $request->query('category');
        $stockStatus = (string) $request->query('stock_status');
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());
        $fromDate = $from !== '' ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to !== '' ? Carbon::parse($to)->endOfDay() : null;
        $dateRange = fn ($query) => $query
            ->when($fromDate, fn ($dateQuery) => $dateQuery->where('occurred_at', '>=', $fromDate))
            ->when($toDate, fn ($dateQuery) => $dateQuery->where('occurred_at', '<=', $toDate));

        $products = Product::query()
            ->with('category')
            ->withExists(['inventoryBatches as has_opening_stock' => fn ($query) => $query->where('source', InventoryBatch::SOURCE_OPENING_STOCK)])
            ->withSum('inventoryBatches as costed_stock_remaining', 'remaining_quantity')
            ->withSum(['inventoryMovements as sold_units' => fn ($query) => $dateRange($query->where('type', InventoryMovement::TYPE_SALE_PAID))], 'quantity')
            ->withSum(['inventoryMovements as gross_profit' => fn ($query) => $dateRange($query->where('type', InventoryMovement::TYPE_SALE_PAID))], 'profit')
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

        $allProducts = Product::all(['stock_quantity', 'warehouse_stock_quantity', 'reorder_level']);
        $periodSales = InventoryMovement::query()->where('type', InventoryMovement::TYPE_SALE_PAID);
        $dateRange($periodSales);
        $currentUnits = $allProducts->sum('stock_quantity') + $allProducts->sum('warehouse_stock_quantity');

        $stats = [
            'display_units' => $allProducts->sum('stock_quantity'),
            'warehouse_units' => $allProducts->sum('warehouse_stock_quantity'),
            'stock_value' => (float) InventoryBatch::selectRaw('COALESCE(SUM(remaining_quantity * unit_cost), 0) as value')->value('value'),
            'low_display_products' => $allProducts->filter(fn (Product $product): bool => $product->stock_quantity <= $product->reorder_level)->count(),
            'period_opening_units' => $fromDate ? $inventory->stockBalanceBefore($fromDate) : $currentUnits,
            'period_closing_units' => $toDate ? $inventory->stockBalanceUpTo($toDate) : $currentUnits,
            'sold_units' => (int) (clone $periodSales)->sum('quantity'),
            'gross_profit' => (float) (clone $periodSales)->sum('profit'),
        ];

        $movements = InventoryMovement::query()
            ->with('product', 'performer', 'inventoryBatch')
            ->when($fromDate, fn ($query) => $query->where('occurred_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('occurred_at', '<=', $toDate))
            ->latest('occurred_at')
            ->latest()
            ->paginate(10, ['*'], 'movement_page')
            ->withQueryString();

        return view('admin.inventory.index', [
            'products' => $products,
            'categories' => ProductCategory::orderBy('name')->get(),
            'movementLabels' => InventoryMovement::labels(),
            'movements' => $movements,
            'stats' => $stats,
            'search' => $search,
            'categoryId' => $categoryId,
            'stockStatus' => $stockStatus,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function opening(Request $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            '_modal' => ['nullable', 'string'],
            'occurred_at' => ['required', 'date'],
            'warehouse_quantity' => ['required', 'integer', 'min:0'],
            'display_quantity' => ['required', 'integer', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $inventory->recordOpeningStock($product, $data, auth()->id());

        return back()->with('status', 'Opening stock recorded for '.$product->name.'.');
    }

    public function intake(Request $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            '_modal' => ['nullable', 'string'],
            'occurred_at' => ['required', 'date'],
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
            'occurred_at' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $inventory->transferToDisplay($product, (int) $data['quantity'], auth()->id(), $data['notes'] ?? null, $data['occurred_at']);

        return back()->with('status', $product->name.' moved from warehouse to website/display stock.');
    }

    public function updateMovement(Request $request, InventoryMovement $inventoryMovement, InventoryService $inventory): RedirectResponse
    {
        $rules = [
            '_modal' => ['nullable', 'string'],
            'occurred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($inventoryMovement->type === InventoryMovement::TYPE_OPENING_STOCK) {
            $rules += [
                'warehouse_quantity' => ['required', 'integer', 'min:0'],
                'display_quantity' => ['required', 'integer', 'min:0'],
                'unit_cost' => ['required', 'numeric', 'min:0'],
                'unit_price' => ['required', 'numeric', 'min:0'],
            ];
        } elseif ($inventoryMovement->type === InventoryMovement::TYPE_STOCK_INTAKE) {
            $rules += [
                'quantity' => ['required', 'integer', 'min:1'],
                'unit_cost' => ['required', 'numeric', 'min:0'],
                'unit_price' => ['required', 'numeric', 'min:0'],
            ];
        } else {
            $rules += [
                'quantity' => ['required', 'integer', 'min:1'],
            ];
        }

        $inventory->updateMovement($inventoryMovement, $request->validate($rules), auth()->id());

        return back()->with('status', 'Inventory movement updated.');
    }

    public function destroyMovement(InventoryMovement $inventoryMovement, InventoryService $inventory): RedirectResponse
    {
        $inventory->deleteMovement($inventoryMovement);

        return back()->with('status', 'Inventory movement deleted.');
    }

    public function import(Request $request, InventoryImportService $imports): RedirectResponse
    {
        $data = $request->validate([
            'inventory_csv' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $result = $imports->import($data['inventory_csv'], auth()->id());
        $status = $result['imported'].' row'.($result['imported'] === 1 ? '' : 's').' imported.';

        if ($result['errors']) {
            $status .= ' Review the skipped rows below.';
        }

        return back()
            ->with('status', $status)
            ->with('inventory_import_errors', $result['errors']);
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, InventoryImportService::HEADERS);

            Product::query()
                ->with('category')
                ->orderBy('name')
                ->chunk(200, function ($products) use ($output): void {
                    foreach ($products as $product) {
                        fputcsv($output, [
                            InventoryBatch::SOURCE_OPENING_STOCK,
                            now()->toDateString(),
                            $product->sku,
                            $product->name,
                            $product->category?->name,
                            $product->description,
                            $product->brand,
                            $product->unit,
                            (int) $product->cost_price,
                            (int) $product->price,
                            (int) $product->warehouse_stock_quantity,
                            (int) $product->stock_quantity,
                            (int) $product->reorder_level,
                            $product->is_active ? 1 : 0,
                            $product->is_featured ? 1 : 0,
                            'Current inventory snapshot exported from EduKit.',
                        ]);
                    }
                });

            fclose($output);
        }, 'edukit-inventory-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function template(InventoryImportService $imports): StreamedResponse
    {
        return response()->streamDownload(function () use ($imports): void {
            $output = fopen('php://output', 'wb');

            foreach ($imports->templateRows() as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, 'edukit-inventory-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
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
