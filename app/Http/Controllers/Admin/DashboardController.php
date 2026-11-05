<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\School;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = $request->query('period', 'month');
        if (! in_array($period, ['week', 'month', 'year', 'custom'], true)) {
            $period = 'month';
        }

        if ($period === 'custom') {
            $dates = $request->validate([
                'from' => ['required', 'date'],
                'to' => ['required', 'date', 'after_or_equal:from'],
            ]);
            $periodFrom = $dates['from'];
            $periodTo = $dates['to'];
        } else {
            $today = now();
            $periodFrom = match ($period) {
                'week' => $today->copy()->startOfWeek()->toDateString(),
                'year' => $today->copy()->startOfYear()->toDateString(),
                default => $today->copy()->startOfMonth()->toDateString(),
            };
            $periodTo = $today->toDateString();
        }

        $periodStart = Carbon::parse($periodFrom)->startOfDay();
        $periodEnd = Carbon::parse($periodTo)->endOfDay();
        $includeDemo = $request->boolean('include_demo');

        $paidOrders = ShoppingList::query()
            ->where('payment_status', ShoppingList::PAYMENT_PAID)
            ->whereBetween('paid_at', [$periodStart, $periodEnd])
            ->when(! $includeDemo, fn ($query) => $query->where(fn ($provider) => $provider
                ->whereNull('payment_provider')
                ->orWhere('payment_provider', '!=', 'demo')));

        $paidItems = ShoppingListItem::query()
            ->whereIn('shopping_list_id', (clone $paidOrders)->select('id'));

        $performance = [
            'orders_received' => ShoppingList::query()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->count(),
            'paid_orders' => (clone $paidOrders)->count(),
            'item_revenue' => (clone $paidItems)->sum('line_total'),
            'gross_profit' => (clone $paidItems)->sum('profit_total'),
        ];

        $periodLabel = match ($period) {
            'week' => 'This week',
            'year' => 'This year',
            'custom' => 'Custom date range',
            default => 'This month',
        };

        $stats = [
            'products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'pending_suppliers' => Supplier::where('is_approved', false)->count(),
            'approved_suppliers' => Supplier::where('is_approved', true)->count(),
            'pending_shopping_lists' => ShoppingList::where('status', ShoppingList::STATUS_PENDING)->count(),
            'payment_exceptions' => ShoppingList::whereNotNull('payment_exception')->count(),
            'orders_awaiting_driver_assignment' => ShoppingList::where('payment_status', ShoppingList::PAYMENT_PAID)
                ->whereNull('assigned_driver_id')->whereNull('payment_exception')
                ->whereNull('customer_received_at')->whereNull('delivery_confirmed_at')->count(),
            'shopping_lists' => ShoppingList::count(),
            'schools' => School::count(),
            'active_schools' => School::where('is_active', true)->count(),
            'pending_drivers' => Driver::where('is_approved', false)->count(),
            'approved_drivers' => Driver::where('is_approved', true)->count(),
            'display_stock' => Product::sum('stock_quantity'),
            'warehouse_stock' => Product::sum('warehouse_stock_quantity'),
            'low_display_stock' => Product::whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
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

        $orderQueues = [
            [
                'label' => 'New list requests',
                'description' => 'Uploaded school lists to review and price.',
                'count' => ShoppingList::where('source', ShoppingList::SOURCE_UPLOAD)
                    ->whereIn('status', [ShoppingList::STATUS_PENDING, ShoppingList::STATUS_REVIEWING])->count(),
                'route' => route('admin.shopping-lists.index'),
                'permission' => 'shopping-lists.view',
            ],
            [
                'label' => 'Awaiting payment',
                'description' => 'Quotes customers have not paid yet.',
                'count' => ShoppingList::where('status', ShoppingList::STATUS_QUOTED)
                    ->where('payment_status', ShoppingList::PAYMENT_UNPAID)->count(),
                'route' => route('admin.invoices.index', ['status' => ShoppingList::STATUS_QUOTED, 'payment_status' => ShoppingList::PAYMENT_UNPAID]),
                'permission' => 'invoices.view',
            ],
            [
                'label' => 'Payment processing',
                'description' => 'Transactions awaiting provider confirmation.',
                'count' => ShoppingList::where('payment_status', ShoppingList::PAYMENT_PENDING)->count(),
                'route' => route('admin.invoices.index', ['payment_status' => ShoppingList::PAYMENT_PENDING]),
                'permission' => 'invoices.view',
            ],
            [
                'label' => 'Awaiting driver assignment',
                'description' => 'Paid, issue-free orders ready for an approved driver.',
                'count' => $stats['orders_awaiting_driver_assignment'],
                'route' => route('admin.invoices.index', ['payment_status' => ShoppingList::PAYMENT_PAID, 'delivery_status' => 'awaiting_driver_assignment']),
                'permission' => 'invoices.view',
            ],
            [
                'label' => 'Ready to dispatch',
                'description' => 'Paid orders assigned to a driver, not started yet.',
                'count' => ShoppingList::whereNotNull('assigned_driver_id')
                    ->where('payment_status', ShoppingList::PAYMENT_PAID)->whereNull('payment_exception')
                    ->whereNull('driver_started_at')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at')->count(),
                'route' => route('admin.invoices.index', ['delivery_status' => 'ready_for_delivery']),
                'permission' => 'invoices.view',
            ],
            [
                'label' => 'In transit',
                'description' => 'Drivers have started these deliveries.',
                'count' => ShoppingList::whereNotNull('driver_started_at')->whereNull('driver_reached_at')
                    ->whereNull('customer_received_at')->whereNull('delivery_confirmed_at')->count(),
                'route' => route('admin.invoices.index', ['delivery_status' => 'in_transit']),
                'permission' => 'invoices.view',
            ],
            [
                'label' => 'Awaiting customer confirmation',
                'description' => 'Driver arrived; customer still needs to confirm receipt.',
                'count' => ShoppingList::whereNotNull('driver_reached_at')
                    ->whereNull('customer_received_at')->whereNull('delivery_confirmed_at')->count(),
                'route' => route('admin.invoices.index', ['delivery_status' => 'awaiting_customer_confirmation']),
                'permission' => 'invoices.view',
            ],
            [
                'label' => 'Payment / stock review',
                'description' => 'Exceptions an administrator needs to resolve.',
                'count' => ShoppingList::whereNotNull('payment_exception')
                    ->whereNull('customer_received_at')->whereNull('delivery_confirmed_at')->count(),
                'route' => route('admin.invoices.index', ['issues' => 1]),
                'permission' => 'invoices.view',
            ],
            [
                'label' => 'Delivered',
                'description' => 'Orders confirmed as received by the customer.',
                'count' => ShoppingList::where(fn ($query) => $query->whereNotNull('customer_received_at')->orWhereNotNull('delivery_confirmed_at'))->count(),
                'route' => route('admin.invoices.index', ['delivery_status' => 'delivered']),
                'permission' => 'invoices.view',
            ],
        ];

        return view('admin.dashboard', compact(
            'stats',
            'latestProducts',
            'latestSuppliers',
            'latestShoppingLists',
            'latestDrivers',
            'productCategorySummary',
            'shoppingListStatusSummary',
            'orderQueues',
            'performance',
            'period',
            'periodFrom',
            'periodTo',
            'periodLabel',
            'includeDemo',
        ));
    }
}
