<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DeliveryFeeReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
        $to = isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null;
        $includeDemo = $request->routeIs('admin.reports.delivery-fees') && $request->boolean('include_demo');

        $completedOrders = ShoppingList::query()
            ->where('payment_status', ShoppingList::PAYMENT_PAID)
            ->where(fn ($query) => $query->whereNotNull('customer_received_at')->orWhereNotNull('delivery_confirmed_at'))
            ->when($from, fn ($query) => $query->where(fn ($completion) => $completion
                ->where('customer_received_at', '>=', $from)
                ->orWhere(fn ($fallback) => $fallback->whereNull('customer_received_at')->where('delivery_confirmed_at', '>=', $from))))
            ->when($to, fn ($query) => $query->where(fn ($completion) => $completion
                ->where('customer_received_at', '<=', $to)
                ->orWhere(fn ($fallback) => $fallback->whereNull('customer_received_at')->where('delivery_confirmed_at', '<=', $to))))
            ->when(! $includeDemo, fn ($query) => $query->where(fn ($provider) => $provider
                ->whereNull('payment_provider')
                ->orWhere('payment_provider', '!=', 'demo')));

        $totals = [
            'completed_orders' => (clone $completedOrders)->count(),
            'fees_collected' => (clone $completedOrders)->sum('delivery_fee'),
            'orders_without_driver' => (clone $completedOrders)->whereNull('assigned_driver_id')->count(),
        ];

        $driverTotals = (clone $completedOrders)
            ->select('assigned_driver_id')
            ->selectRaw('COUNT(*) as completed_orders, SUM(COALESCE(delivery_fee, 0)) as fees_collected')
            ->groupBy('assigned_driver_id')
            ->with('assignedDriver')
            ->orderByDesc('fees_collected')
            ->get();

        $orders = (clone $completedOrders)
            ->with('assignedDriver', 'school')
            ->orderByRaw('COALESCE(customer_received_at, delivery_confirmed_at) DESC')
            ->paginate(20)
            ->withQueryString();

        $financeView = $request->routeIs('finance.delivery-fees');

        return view($financeView ? 'finance.delivery-fees' : 'admin.reports.delivery-fees', compact(
            'driverTotals',
            'orders',
            'totals',
            'from',
            'to',
            'includeDemo',
            'financeView',
        ));
    }
}
