<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverPayout;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartnerPerformanceReportController extends Controller
{
    public function drivers(Request $request): View
    {
        $filters = $this->filters($request);
        $rows = $this->driverRows($filters['from'], $filters['to'], $filters['includeDemo']);
        $eligible = $this->eligiblePayouts($filters['from'], $filters['to']);

        return view('admin.reports.driver-performance', [
            'rows' => $rows,
            'totals' => [
                'completed' => $rows->sum('completed_deliveries'),
                'customer_fees' => $rows->sum('customer_fees_collected'),
                'payouts_recorded' => $rows->sum('recorded_payouts'),
                'awaiting_payout' => (clone $eligible)->count(),
            ],
            ...$filters,
        ]);
    }

    public function suppliers(Request $request): View
    {
        $filters = $this->filters($request);
        $items = $this->supplierItems($filters['from'], $filters['to'], $filters['includeDemo']);
        $totals = (clone $items)->selectRaw(
            'COUNT(DISTINCT shopping_list_id) as paid_orders,
             SUM(quantity) as units_sold,
             SUM(line_total) as sales,
             SUM(supplier_payable) as supplier_payable,
             SUM(profit_total) as edukit_margin,
             SUM(CASE WHEN settlement_status = \'earned\' THEN supplier_payable ELSE 0 END) as earned,
             SUM(CASE WHEN settlement_status = \'earned\' THEN 0 ELSE supplier_payable END) as reserved'
        )->first();
        $rows = $this->supplierRows($filters['from'], $filters['to'], $filters['includeDemo']);

        return view('admin.reports.supplier-performance', compact('rows', 'totals') + $filters);
    }

    public function payouts(Request $request): View
    {
        $filters = $this->filters($request, includeDemo: false);
        $driverId = $filters['driverId'];
        $eligibleOrders = $this->eligiblePayouts($filters['from'], $filters['to'])
            ->when($driverId, fn (Builder $query) => $query->where('assigned_driver_id', $driverId))
            ->with('assignedDriver')
            ->orderByRaw('COALESCE(customer_received_at, delivery_confirmed_at) DESC')
            ->paginate(15)
            ->withQueryString();

        $payouts = DriverPayout::query()
            ->whereHas('shoppingList', fn (Builder $query) => $this->applyCompletionDates($query, $filters['from'], $filters['to']))
            ->when($driverId, fn (Builder $query) => $query->where('driver_id', $driverId))
            ->with(['driver', 'shoppingList', 'recorder'])
            ->latest('paid_at')
            ->paginate(15, ['*'], 'payout_page')
            ->withQueryString();

        $totals = [
            'eligible' => (clone $this->eligiblePayouts($filters['from'], $filters['to']))
                ->when($driverId, fn (Builder $query) => $query->where('assigned_driver_id', $driverId))
                ->count(),
            'recorded_count' => (clone $this->payoutsForCompletionPeriod($filters['from'], $filters['to']))
                ->when($driverId, fn (Builder $query) => $query->where('driver_id', $driverId))
                ->count(),
            'recorded_amount' => (clone $this->payoutsForCompletionPeriod($filters['from'], $filters['to']))
                ->when($driverId, fn (Builder $query) => $query->where('driver_id', $driverId))
                ->sum('amount'),
        ];

        $drivers = Driver::query()->orderBy('name')->get(['id', 'name', 'payment_phone']);

        return view('admin.reports.driver-payouts', compact('eligibleOrders', 'payouts', 'totals', 'drivers') + $filters);
    }

    public function recordPayout(Request $request)
    {
        $data = $request->validate([
            'shopping_list_id' => ['required', 'integer', 'exists:shopping_lists,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'transaction_reference' => ['required', 'string', 'max:120', Rule::unique('driver_payouts', 'transaction_reference')],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $order = ShoppingList::query()->whereKey($data['shopping_list_id'])->lockForUpdate()->firstOrFail();

            $isCompleted = $order->customer_received_at || $order->delivery_confirmed_at;
            $isRealPaidOrder = $order->payment_status === ShoppingList::PAYMENT_PAID
                && $order->payment_provider !== 'demo'
                && ! $order->payment_exception;

            if (! $isCompleted || ! $isRealPaidOrder || ! $order->assigned_driver_id) {
                throw ValidationException::withMessages([
                    'shopping_list_id' => 'Only real, paid, completed deliveries with an assigned driver can be recorded for payout.',
                ]);
            }

            if ($order->driverPayout()->exists()) {
                throw ValidationException::withMessages([
                    'shopping_list_id' => 'A payout is already recorded for this delivery.',
                ]);
            }

            $driver = Driver::query()->whereKey($order->assigned_driver_id)->lockForUpdate()->first();
            if (! $driver?->payment_phone) {
                throw ValidationException::withMessages([
                    'shopping_list_id' => 'Add the driver’s Mobile Money payment number before recording this payout.',
                ]);
            }

            DriverPayout::create([
                'shopping_list_id' => $order->id,
                'driver_id' => $driver->id,
                'amount' => $data['amount'],
                'payment_phone' => $driver->payment_phone,
                'transaction_reference' => trim($data['transaction_reference']),
                'paid_at' => Carbon::parse($data['paid_at']),
                'recorded_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('admin.reports.driver-payouts', array_filter([
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'driver_id' => $request->input('driver_id'),
        ], fn ($value) => $value !== null && $value !== ''))
            ->with('status', 'External Mobile Money payout recorded.');
    }

    public function exportDrivers(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $rows = $this->driverRows($filters['from'], $filters['to'], $filters['includeDemo']);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Driver', 'Payment number', 'Completed deliveries', 'Customer fees collected (UGX; not driver wages)', 'Manual payouts recorded (UGX)', 'Completed deliveries awaiting payout record']);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $this->safeCsvText($row->assignedDriver?->name),
                    $this->safeCsvText($row->assignedDriver?->payment_phone),
                    (int) $row->completed_deliveries,
                    (int) $row->customer_fees_collected,
                    (int) $row->recorded_payouts,
                    (int) $row->awaiting_payout_count,
                ]);
            }

            fclose($output);
        }, 'edukit-driver-performance-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportSuppliers(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $rows = $this->supplierRows($filters['from'], $filters['to'], $filters['includeDemo']);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Supplier', 'Paid orders', 'Units sold', 'Sales (UGX)', 'Supplier payable (UGX)', 'Earned after delivery (UGX)', 'Reserved pending delivery (UGX)', 'EduKit margin (UGX)']);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $this->safeCsvText($row->supplier?->business_name),
                    (int) $row->paid_orders,
                    (int) $row->units_sold,
                    (int) $row->sales,
                    (int) $row->supplier_payable,
                    (int) $row->earned,
                    (int) $row->reserved,
                    (int) $row->edukit_margin,
                ]);
            }

            fclose($output);
        }, 'edukit-supplier-performance-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPayouts(Request $request): StreamedResponse
    {
        $filters = $this->filters($request, includeDemo: false);
        $driverId = $filters['driverId'];
        $payouts = $this->payoutsForCompletionPeriod($filters['from'], $filters['to'])
            ->when($driverId, fn (Builder $query) => $query->where('driver_id', $driverId))
            ->with(['driver', 'shoppingList', 'recorder'])
            ->orderBy('paid_at');

        return response()->streamDownload(function () use ($payouts): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Paid at', 'Order reference', 'Driver', 'Payment number', 'Amount (UGX)', 'Mobile Money transaction reference', 'Recorded by', 'Notes']);

            $payouts->chunkById(500, function ($chunk) use ($output): void {
                foreach ($chunk as $payout) {
                    fputcsv($output, [
                        $payout->paid_at?->format('Y-m-d H:i:s'),
                        $this->safeCsvText($payout->shoppingList?->reference),
                        $this->safeCsvText($payout->driver?->name),
                        $this->safeCsvText($payout->payment_phone),
                        $payout->amount,
                        $this->safeCsvText($payout->transaction_reference),
                        $this->safeCsvText($payout->recorder?->name),
                        $this->safeCsvText($payout->notes),
                    ]);
                }
            });

            fclose($output);
        }, 'edukit-driver-payouts-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request, bool $includeDemo = true): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'include_demo' => ['nullable', 'boolean'],
        ]);

        return [
            'from' => isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null,
            'to' => isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null,
            'includeDemo' => $includeDemo && $request->boolean('include_demo'),
            'driverId' => $validated['driver_id'] ?? null,
        ];
    }

    private function driverRows(?Carbon $from, ?Carbon $to, bool $includeDemo)
    {
        $completed = $this->completedOrders($from, $to, $includeDemo);
        $rows = (clone $completed)
            ->whereNotNull('assigned_driver_id')
            ->select('assigned_driver_id')
            ->selectRaw('COUNT(*) as completed_deliveries, SUM(COALESCE(delivery_fee, 0)) as customer_fees_collected')
            ->groupBy('assigned_driver_id')
            ->with('assignedDriver')
            ->orderByDesc('completed_deliveries')
            ->get();

        $paidOut = $this->payoutsForCompletionPeriod($from, $to)
            ->select('driver_id')
            ->selectRaw('COUNT(*) as payout_count, SUM(amount) as amount_paid')
            ->groupBy('driver_id')
            ->get()
            ->keyBy('driver_id');

        $pending = $this->eligiblePayouts($from, $to)
            ->select('assigned_driver_id')
            ->selectRaw('COUNT(*) as awaiting_payout_count')
            ->groupBy('assigned_driver_id')
            ->pluck('awaiting_payout_count', 'assigned_driver_id');

        return $rows->map(function ($row) use ($paidOut, $pending) {
            $payout = $paidOut->get($row->assigned_driver_id);
            $row->payout_count = (int) ($payout?->payout_count ?? 0);
            $row->recorded_payouts = (int) ($payout?->amount_paid ?? 0);
            $row->awaiting_payout_count = (int) ($pending[$row->assigned_driver_id] ?? 0);

            return $row;
        });
    }

    private function supplierRows(?Carbon $from, ?Carbon $to, bool $includeDemo)
    {
        return $this->supplierItems($from, $to, $includeDemo)
            ->select('supplier_id')
            ->selectRaw('COUNT(DISTINCT shopping_list_id) as paid_orders,
                SUM(quantity) as units_sold,
                SUM(line_total) as sales,
                SUM(supplier_payable) as supplier_payable,
                SUM(profit_total) as edukit_margin,
                SUM(CASE WHEN settlement_status = \'earned\' THEN supplier_payable ELSE 0 END) as earned,
                SUM(CASE WHEN settlement_status = \'earned\' THEN 0 ELSE supplier_payable END) as reserved')
            ->groupBy('supplier_id')
            ->with('supplier')
            ->orderByDesc('sales')
            ->get();
    }

    private function supplierItems(?Carbon $from, ?Carbon $to, bool $includeDemo): Builder
    {
        return ShoppingListItem::query()
            ->where('fulfilment_source', 'supplier')
            ->whereNotNull('supplier_id')
            ->whereHas('shoppingList', function (Builder $query) use ($from, $to, $includeDemo): void {
                $query->where('payment_status', ShoppingList::PAYMENT_PAID)
                    ->when(! $includeDemo, fn (Builder $paid) => $paid->where(fn (Builder $provider) => $provider
                        ->whereNull('payment_provider')->orWhere('payment_provider', '!=', 'demo')))
                    ->when($from, fn (Builder $paid) => $paid->where('paid_at', '>=', $from))
                    ->when($to, fn (Builder $paid) => $paid->where('paid_at', '<=', $to));
            });
    }

    private function completedOrders(?Carbon $from, ?Carbon $to, bool $includeDemo): Builder
    {
        return ShoppingList::query()
            ->where('payment_status', ShoppingList::PAYMENT_PAID)
            ->when(! $includeDemo, fn (Builder $query) => $query->where(fn (Builder $provider) => $provider
                ->whereNull('payment_provider')->orWhere('payment_provider', '!=', 'demo')))
            ->where(fn (Builder $query) => $this->applyCompletionDates($query, $from, $to));
    }

    private function eligiblePayouts(?Carbon $from, ?Carbon $to): Builder
    {
        return $this->completedOrders($from, $to, false)
            ->whereNotNull('assigned_driver_id')
            ->whereNull('payment_exception')
            ->whereDoesntHave('driverPayout');
    }

    private function payoutsForCompletionPeriod(?Carbon $from, ?Carbon $to): Builder
    {
        return DriverPayout::query()
            ->whereHas('shoppingList', fn (Builder $query) => $this->applyCompletionDates($query, $from, $to));
    }

    private function applyCompletionDates(Builder $query, ?Carbon $from, ?Carbon $to): Builder
    {
        return $query->where(function (Builder $completion) use ($from, $to): void {
            $completion->where(function (Builder $received) use ($from, $to): void {
                $received->whereNotNull('customer_received_at')
                    ->when($from, fn (Builder $date) => $date->where('customer_received_at', '>=', $from))
                    ->when($to, fn (Builder $date) => $date->where('customer_received_at', '<=', $to));
            })->orWhere(function (Builder $fallback) use ($from, $to): void {
                $fallback->whereNull('customer_received_at')
                    ->whereNotNull('delivery_confirmed_at')
                    ->when($from, fn (Builder $date) => $date->where('delivery_confirmed_at', '>=', $from))
                    ->when($to, fn (Builder $date) => $date->where('delivery_confirmed_at', '<=', $to));
            });
        });
    }

    private function safeCsvText(?string $value): ?string
    {
        if ($value !== null && ! is_numeric($value) && preg_match('/^[\x00-\x20]*[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
