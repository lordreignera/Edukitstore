<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $completedOrders = $this->completedOrdersQuery($from, $to, $includeDemo);

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

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
        $to = isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null;
        $includeDemo = $request->routeIs('admin.reports.delivery-fees.export')
            && $request->boolean('include_demo');

        $orders = $this->completedOrdersQuery($from, $to, $includeDemo)
            ->with('assignedDriver', 'school')
            ->orderBy('id');

        return response()->streamDownload(function () use ($orders): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Completed at', 'Order reference', 'Customer', 'Phone', 'Email', 'School / destination',
                'Driver', 'Driver payment number', 'Delivery preference', 'Payment provider',
                'Customer fee collected (UGX)',
            ]);

            $orders->chunkById(500, function ($chunk) use ($output): void {
                foreach ($chunk as $order) {
                    $completedAt = $order->customer_received_at ?: $order->delivery_confirmed_at;
                    fputcsv($output, [
                        $completedAt?->format('Y-m-d H:i:s'),
                        $this->safeCsvText($order->reference),
                        $this->safeCsvText($order->parent_name),
                        $this->safeCsvText($order->phone),
                        $this->safeCsvText($order->email),
                        $this->safeCsvText($order->school?->name ?? $order->school_name ?? $order->delivery_location),
                        $this->safeCsvText($order->assignedDriver?->name),
                        $this->safeCsvText($order->assignedDriver?->payment_phone),
                        $this->safeCsvText($order->delivery_preference),
                        $order->payment_provider === 'demo' ? 'demo (test)' : $this->safeCsvText($order->payment_provider),
                        $order->delivery_fee,
                    ]);
                }
            });

            fclose($output);
        }, 'edukit-completed-order-fees-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function completedOrdersQuery(?Carbon $from, ?Carbon $to, bool $includeDemo): Builder
    {
        return ShoppingList::query()
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
    }

    private function safeCsvText(?string $value): ?string
    {
        if ($value !== null && ! is_numeric($value) && preg_match('/^[\x00-\x20]*[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
