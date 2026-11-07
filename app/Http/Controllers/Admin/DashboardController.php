<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Product;
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
        $today = now();
        $todayStart = $today->copy()->startOfDay();
        $todayEnd = $today->copy()->endOfDay();
        $todayDate = $today->toDateString();
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

        $dailyOrders = ShoppingList::query()
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->selectRaw('DATE(created_at) as trend_date, COUNT(*) as amount')
            ->groupByRaw('DATE(created_at)')
            ->pluck('amount', 'trend_date')
            ->all();

        $dailyRevenue = ShoppingListItem::query()
            ->join('shopping_lists', 'shopping_lists.id', '=', 'shopping_list_items.shopping_list_id')
            ->where('shopping_lists.payment_status', ShoppingList::PAYMENT_PAID)
            ->whereBetween('shopping_lists.paid_at', [$periodStart, $periodEnd])
            ->when(! $includeDemo, fn ($query) => $query->where(fn ($provider) => $provider
                ->whereNull('shopping_lists.payment_provider')
                ->orWhere('shopping_lists.payment_provider', '!=', 'demo')))
            ->selectRaw('DATE(shopping_lists.paid_at) as trend_date, SUM(shopping_list_items.line_total) as amount')
            ->groupByRaw('DATE(shopping_lists.paid_at)')
            ->pluck('amount', 'trend_date')
            ->all();

        $trend = $this->buildTrend($dailyOrders, $dailyRevenue, $periodStart, $periodEnd);

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
                ->whereHas('lineItems', fn ($items) => $items->where('quantity', '>', 0))
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

        // Keep today's intake/completion separate from the selected performance period and live open queues.
        $todayFlow = [
            'date' => $todayDate,
            'received' => ShoppingList::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'awaiting_fulfilment' => $stats['orders_awaiting_driver_assignment'],
            'assigned_to_drivers' => ShoppingList::query()
                ->where('status', ShoppingList::STATUS_QUOTED)
                ->where('payment_status', ShoppingList::PAYMENT_PAID)
                ->whereNotNull('assigned_driver_id')
                ->whereNotNull('packed_at')
                ->whereNull('payment_exception')
                ->whereNull('customer_received_at')
                ->whereNull('delivery_confirmed_at')
                ->count(),
            'completed' => ShoppingList::query()
                ->whereRaw('DATE(COALESCE(customer_received_at, delivery_confirmed_at)) = ?', [$todayDate])
                ->count(),
        ];

        $latestOrders = ShoppingList::query()
            ->with(['assignedDriver', 'school'])
            ->withCount('lineItems')
            ->latest()
            ->take(8)
            ->get();

        $actionQueues = [
            [
                'label' => 'New list requests',
                'description' => 'Uploaded school lists to review and price.',
                'count' => ShoppingList::where('source', ShoppingList::SOURCE_UPLOAD)
                    ->whereIn('status', [ShoppingList::STATUS_PENDING, ShoppingList::STATUS_REVIEWING])->count(),
                'route' => route('admin.shopping-lists.index', ['review_queue' => 1]),
                'permission' => 'shopping-lists.view',
            ],
            [
                'label' => 'Payment / stock review',
                'description' => 'Resolve payment exceptions before fulfilment continues.',
                'count' => ShoppingList::whereNotNull('payment_exception')
                    ->whereNull('customer_received_at')->whereNull('delivery_confirmed_at')->count(),
                'route' => route('admin.invoices.index', ['issues' => 1, 'delivery_status' => 'under_review']),
                'permission' => 'invoices.view',
            ],
            [
                'label' => 'Supplier approvals',
                'description' => 'Review supplier applications and verification details.',
                'count' => $stats['pending_suppliers'],
                'route' => route('admin.suppliers.index', ['status' => 'pending']),
                'permission' => 'suppliers.view',
            ],
            [
                'label' => 'Driver approvals',
                'description' => 'Approve delivery partners before assigning orders.',
                'count' => $stats['pending_drivers'],
                'route' => route('admin.drivers.index', ['status' => 'pending']),
                'permission' => 'drivers.view',
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
                'label' => 'Low display stock',
                'description' => 'Review products at or below their reorder threshold.',
                'count' => $stats['low_display_stock'],
                'route' => route('admin.inventory.index', ['stock_status' => 'low_display']),
                'permission' => 'inventory.view',
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
        ];

        return view('admin.dashboard', compact(
            'stats',
            'todayFlow',
            'latestOrders',
            'actionQueues',
            'performance',
            'trend',
            'period',
            'periodFrom',
            'periodTo',
            'periodLabel',
            'includeDemo',
        ));
    }

    private function buildTrend(array $dailyOrders, array $dailyRevenue, Carbon $from, Carbon $to): array
    {
        $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $granularity = $days <= 31 ? 'day' : ($days <= 366 ? 'week' : 'month');
        $keyFor = static function (string $date) use ($granularity): string {
            $day = Carbon::parse($date);

            return match ($granularity) {
                'week' => $day->startOfWeek()->toDateString(),
                'month' => $day->startOfMonth()->toDateString(),
                default => $day->toDateString(),
            };
        };

        $sumByBucket = static function (array $daily) use ($keyFor): array {
            $buckets = [];
            foreach ($daily as $date => $amount) {
                $key = $keyFor((string) $date);
                $buckets[$key] = ($buckets[$key] ?? 0) + (float) $amount;
            }

            return $buckets;
        };

        $orders = $sumByBucket($dailyOrders);
        $revenue = $sumByBucket($dailyRevenue);
        $cursor = match ($granularity) {
            'week' => $from->copy()->startOfWeek(),
            'month' => $from->copy()->startOfMonth(),
            default => $from->copy()->startOfDay(),
        };
        $lastDay = $to->copy()->startOfDay();
        $points = [];

        while ($cursor->lte($lastDay)) {
            $key = $cursor->toDateString();
            $points[] = [
                'key' => $key,
                'label' => match ($granularity) {
                    'week' => $cursor->format('M j'),
                    'month' => $cursor->format('M Y'),
                    default => $cursor->format('M j'),
                },
                'orders' => (int) ($orders[$key] ?? 0),
                'revenue' => (float) ($revenue[$key] ?? 0),
            ];
            $cursor = match ($granularity) {
                'week' => $cursor->addWeek(),
                'month' => $cursor->addMonth(),
                default => $cursor->addDay(),
            };
        }

        return ['granularity' => $granularity, 'points' => $points];
    }
}
