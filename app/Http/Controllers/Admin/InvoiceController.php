<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Services\InventoryService;
use App\Services\InvoiceWorkflow;
use App\Services\PushNotificationService;
use App\Services\SupplierSaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        [$query, $filters] = $this->filteredInvoices($request);

        return view('admin.invoices.index', [
            'invoices' => $query->paginate(15)->withQueryString(),
            'drivers' => Driver::where('is_approved', true)->orderBy('name')->get(),
            'statuses' => ShoppingList::statuses(),
            'paymentStatuses' => ShoppingList::paymentStatuses(),
            'deliveryStatuses' => ShoppingList::deliveryStatuses(),
            ...$filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$orders] = $this->filteredInvoices($request);
        $orders->reorder('id');

        return response()->streamDownload(function () use ($orders): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Order reference', 'Received at', 'Paid at', 'Source', 'Customer', 'Phone', 'Email',
                'School', 'Learner', 'Items', 'Order status', 'Payment status', 'Payment provider',
                'Payment reference', 'Delivery status', 'Driver', 'Delivery preference', 'Delivery location',
                'Order total (UGX)', 'Items subtotal (UGX)', 'Delivery / convenience fee (UGX)',
                'EduKit gross profit (UGX)', 'Payment exception',
            ]);

            $orders->chunkById(500, function ($chunk) use ($output): void {
                foreach ($chunk as $order) {
                    fputcsv($output, [
                        $this->safeCsvText($order->reference),
                        $order->created_at?->format('Y-m-d H:i:s'),
                        $order->paid_at?->format('Y-m-d H:i:s'),
                        $order->source === ShoppingList::SOURCE_CART ? 'Cart' : 'Uploaded list',
                        $this->safeCsvText($order->parent_name),
                        $this->safeCsvText($order->phone),
                        $this->safeCsvText($order->email),
                        $this->safeCsvText($order->school?->name ?? $order->school_name),
                        $this->safeCsvText($order->learner_name),
                        (int) $order->item_count,
                        ShoppingList::statuses()[$order->status] ?? $order->status,
                        ShoppingList::paymentStatuses()[$order->payment_status] ?? $order->payment_status,
                        $order->payment_provider === 'demo' ? 'demo (test)' : $this->safeCsvText($order->payment_provider),
                        $this->safeCsvText($order->payment_reference),
                        ShoppingList::deliveryStatuses()[$order->deliveryStatus()] ?? $order->deliveryStatus(),
                        $this->safeCsvText($order->assignedDriver?->name),
                        $this->safeCsvText($order->delivery_preference),
                        $this->safeCsvText($order->delivery_location),
                        $order->estimated_total,
                        $order->items_subtotal,
                        $order->delivery_fee,
                        $order->realized_profit,
                        $this->safeCsvText($order->payment_exception),
                    ]);
                }
            });

            fclose($output);
        }, 'edukit-orders-fulfilment-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(ShoppingList $invoice): View
    {
        return view('admin.invoices.show', [
            'invoice' => $invoice->load('assignedDriver', 'packedBy', 'manualRefundRecordedBy', 'deliveryConfirmer', 'driverStartedBy', 'driverReachedBy', 'school.district', 'lineItems.product', 'paymentAttempts'),
            'statuses' => ShoppingList::statuses(),
            'drivers' => Driver::query()
                ->where('is_approved', true)
                ->where(fn ($query) => $query->where('is_available', true)->orWhere('id', $invoice->assigned_driver_id ?: 0))
                ->orderBy('name')
                ->get(),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'sku', 'price']),
        ]);
    }

    public function print(ShoppingList $invoice): View
    {
        return view('admin.invoices.print', [
            'invoice' => $invoice->load('assignedDriver', 'school.district', 'lineItems.product'),
        ]);
    }

    public function update(Request $request, ShoppingList $invoice, InvoiceWorkflow $workflow, PushNotificationService $pushNotifications): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ShoppingList::statuses()))],
            'estimated_total' => ['nullable', 'integer', 'min:0'],
            'delivery_fee' => ['nullable', 'integer', 'min:0'],
            'assigned_driver_id' => ['nullable', 'exists:drivers,id'],
        ]);

        if ($invoice->source === ShoppingList::SOURCE_UPLOAD && $request->boolean('items_present')) {
            $rows = collect($request->input('items', []))
                ->filter(fn ($row) => is_array($row) && ! empty($row['product_id']))
                ->values()->all();
            $data['items'] = validator(['items' => $rows], [
                'items' => ['array', 'max:100'],
                'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
                'items.*.unit_price' => ['required', 'integer', 'min:1'],
            ])->validate()['items'];
        }

        $previousDriverId = $invoice->assigned_driver_id;
        $updatedInvoice = $workflow->updateFromAdmin($invoice, $data, auth()->id());

        if ($updatedInvoice->assigned_driver_id && (int) $updatedInvoice->assigned_driver_id !== (int) $previousDriverId) {
            $driverUser = Driver::with('user')->find($updatedInvoice->assigned_driver_id)?->user;

            if ($driverUser) {
                $pushNotifications->sendTo($driverUser, [
                    'title' => 'New delivery assigned',
                    'body' => "Order {$updatedInvoice->reference} is ready in your deliveries.",
                    'url' => route('driver.deliveries.index', ['status' => 'ready']),
                    'tag' => 'edukit-driver-order-'.$updatedInvoice->id,
                ]);
            }
        }

        return back()->with('status', 'Order updated.');
    }

    public function resolvePaidStock(ShoppingList $invoice, InvoiceWorkflow $workflow, InventoryService $inventory, SupplierSaleService $supplierSales): RedirectResponse
    {
        $workflow->resolvePaidStock($invoice, $inventory, $supplierSales);

        return back()->with('status', 'Stock allocation is complete. Review the product quantities, then pack and assign a driver.');
    }

    public function packAndAssign(Request $request, ShoppingList $invoice, InvoiceWorkflow $workflow, InventoryService $inventory, SupplierSaleService $supplierSales, PushNotificationService $pushNotifications, \App\Services\OrderNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.unavailable_quantity' => ['required', 'integer', 'min:0'],
            'items.*.unavailable_reason' => ['nullable', Rule::in(array_keys(ShoppingListItem::unavailableReasons()))],
            'items.*.unavailable_note' => ['nullable', 'string', 'max:500'],
            'packed_items_confirmed' => ['nullable', 'accepted'],
            'assigned_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
        ]);

        $before = $invoice->lineItems()->get()->mapWithKeys(fn (ShoppingListItem $item) => [
            $item->id => [(int) $item->unavailable_quantity, $item->unavailable_reason, $item->unavailable_note],
        ])->all();
        $previousDriverId = $invoice->assigned_driver_id;
        $updatedInvoice = $workflow->packAndAssign(
            $invoice,
            $data['items'],
            $request->boolean('packed_items_confirmed'),
            isset($data['assigned_driver_id']) ? (int) $data['assigned_driver_id'] : null,
            (int) auth()->id(),
            $inventory,
            $supplierSales,
        );

        $after = $updatedInvoice->lineItems->mapWithKeys(fn (ShoppingListItem $item) => [
            $item->id => [(int) $item->unavailable_quantity, $item->unavailable_reason, $item->unavailable_note],
        ])->all();
        if ($before !== $after) {
            $notifications->itemsUnavailable($updatedInvoice);
        }

        if ($updatedInvoice->assigned_driver_id && (int) $updatedInvoice->assigned_driver_id !== (int) $previousDriverId) {
            $driverUser = Driver::with('user')->find($updatedInvoice->assigned_driver_id)?->user;
            if ($driverUser) {
                $pushNotifications->sendTo($driverUser, [
                    'title' => 'Packed order assigned',
                    'body' => "Order {$updatedInvoice->reference} is packed and ready in your deliveries.",
                    'url' => route('driver.deliveries.index', ['status' => 'ready']),
                    'tag' => 'edukit-driver-order-'.$updatedInvoice->id,
                ]);
            }
        }

        if ($updatedInvoice->allItemsUnavailable()) {
            $status = 'Availability saved: all items are unavailable. Record the manual refund to close this order.';
        } else {
            $packedUnits = (int) $updatedInvoice->lineItems->sum('quantity');
            $driverName = $updatedInvoice->assignedDriver?->name;
            $status = "Fulfilment saved: {$packedUnits} unit(s) packed".($driverName ? " and assigned to {$driverName}." : '.');
            if ($updatedInvoice->lineItems->sum('unavailable_quantity') > 0) {
                $status .= ' Unavailable items and refund details are shown below.';
            }
        }

        return back()->with('status', $status);
    }

    public function recordManualRefund(Request $request, ShoppingList $invoice, InvoiceWorkflow $workflow, \App\Services\OrderNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'manual_refund_reference' => ['required', 'string', 'max:160', Rule::unique('shopping_lists', 'manual_refund_reference')],
        ]);

        $updatedInvoice = $workflow->recordManualRefund($invoice, (int) auth()->id(), trim($data['manual_refund_reference']));
        $notifications->manualRefundRecorded($updatedInvoice);

        return back()->with('status', 'Manual refund recorded against the external transaction reference.');
    }

    /**
     * @return array{0: Builder, 1: array<string, mixed>}
     */
    private function filteredInvoices(Request $request): array
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');
        $paymentStatus = (string) $request->query('payment_status');
        $deliveryStatus = (string) $request->query('delivery_status');
        $driverId = (string) $request->query('driver_id');
        $source = (string) $request->query('source');
        $issuesOnly = $request->boolean('issues');
        $dateFilters = $request->validate([
            'date_field' => ['nullable', Rule::in(['created', 'paid', 'completed'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'exclude_demo' => ['nullable', 'boolean'],
        ]);
        $dateField = $dateFilters['date_field'] ?? '';
        $dateFrom = $dateFilters['from'] ?? '';
        $dateTo = $dateFilters['to'] ?? '';
        $excludeDemo = $request->boolean('exclude_demo');

        $query = ShoppingList::query()
            ->with('assignedDriver', 'school.district')
            ->withSum('lineItems as realized_profit', 'profit_total')
            ->withCount('lineItems as item_count')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($invoiceQuery) use ($search) {
                    $invoiceQuery->where('reference', 'like', "%{$search}%")
                        ->orWhere('parent_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('school_name', 'like', "%{$search}%")
                        ->orWhere('learner_name', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($status, ShoppingList::statuses()), fn ($query) => $query->where('status', $status))
            ->when(array_key_exists($paymentStatus, ShoppingList::paymentStatuses()), fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when(in_array($source, [ShoppingList::SOURCE_CART, ShoppingList::SOURCE_UPLOAD], true), fn ($query) => $query->where('source', $source))
            ->when($dateField === 'created' && $dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateField === 'created' && $dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($dateField === 'paid' && $dateFrom !== '', fn ($query) => $query->whereDate('paid_at', '>=', $dateFrom))
            ->when($dateField === 'paid' && $dateTo !== '', fn ($query) => $query->whereDate('paid_at', '<=', $dateTo))
            ->when($dateField === 'completed' && $dateFrom !== '', fn ($query) => $query->whereRaw('DATE(COALESCE(customer_received_at, delivery_confirmed_at)) >= ?', [$dateFrom]))
            ->when($dateField === 'completed' && $dateTo !== '', fn ($query) => $query->whereRaw('DATE(COALESCE(customer_received_at, delivery_confirmed_at)) <= ?', [$dateTo]))
            ->when($excludeDemo, fn ($query) => $query->where(fn ($provider) => $provider
                ->whereNull('payment_provider')->orWhere('payment_provider', '!=', 'demo')))
            ->when($issuesOnly, fn ($query) => $query->whereNotNull('payment_exception'))
            ->when($driverId !== '', fn ($query) => $query->where('assigned_driver_id', $driverId))
            ->when($deliveryStatus === 'awaiting_driver_assignment', fn ($query) => $query
                ->where('payment_status', ShoppingList::PAYMENT_PAID)
                ->whereNull('assigned_driver_id')->whereNull('payment_exception')
                ->whereHas('lineItems', fn ($items) => $items->where('quantity', '>', 0))
                ->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'unassigned', fn ($query) => $query->whereNull('assigned_driver_id')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'awaiting_payment', fn ($query) => $query->whereNotNull('assigned_driver_id')->where('payment_status', '!=', ShoppingList::PAYMENT_PAID)->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'ready_to_pack', fn ($query) => $query->where('payment_status', ShoppingList::PAYMENT_PAID)
                ->whereNull('packed_at')->whereNull('assigned_driver_id')->whereNull('payment_exception')
                ->whereHas('lineItems', fn ($items) => $items->where('quantity', '>', 0))
                ->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'packed', fn ($query) => $query->where('payment_status', ShoppingList::PAYMENT_PAID)
                ->whereNotNull('packed_at')->whereNull('assigned_driver_id')->whereNull('payment_exception')
                ->whereHas('lineItems', fn ($items) => $items->where('quantity', '>', 0))
                ->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'refund_pending', fn ($query) => $query->where('payment_status', ShoppingList::PAYMENT_PAID)
                ->whereNotNull('packed_at')->whereNull('manual_refunded_at')->whereHas('lineItems', fn ($items) => $items->where('unavailable_quantity', '>', 0))
                ->whereDoesntHave('lineItems', fn ($items) => $items->where('quantity', '>', 0)))
            ->when($deliveryStatus === 'refunded', fn ($query) => $query->where('payment_status', ShoppingList::PAYMENT_REFUNDED))
            ->when($deliveryStatus === 'cancelled', fn ($query) => $query->where('status', ShoppingList::STATUS_CANCELLED)
                ->where('payment_status', '!=', ShoppingList::PAYMENT_REFUNDED))
            ->when($deliveryStatus === 'ready_for_delivery', fn ($query) => $query->whereNotNull('assigned_driver_id')->where('payment_status', ShoppingList::PAYMENT_PAID)->whereNotNull('packed_at')->whereNull('payment_exception')->whereNull('driver_started_at')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'assigned', fn ($query) => $query->where('status', ShoppingList::STATUS_QUOTED)
                ->whereNotNull('assigned_driver_id')->where('payment_status', ShoppingList::PAYMENT_PAID)->whereNotNull('packed_at')->whereNull('payment_exception')
                ->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'in_transit', fn ($query) => $query->whereNotNull('driver_started_at')->whereNull('driver_reached_at')->whereNull('customer_received_at'))
            ->when($deliveryStatus === 'awaiting_customer_confirmation', fn ($query) => $query->whereNotNull('driver_reached_at')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'under_review', fn ($query) => $query->whereNotNull('payment_exception')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'delivered', fn ($query) => $query->where(function ($statusQuery) {
                $statusQuery->whereNotNull('customer_received_at')->orWhereNotNull('delivery_confirmed_at');
            }))
            ->latest();

        return [$query, [
            'search' => $search,
            'selectedStatus' => $status,
            'selectedPaymentStatus' => $paymentStatus,
            'selectedDeliveryStatus' => $deliveryStatus,
            'selectedDriverId' => $driverId,
            'selectedSource' => $source,
            'issuesOnly' => $issuesOnly,
            'dateField' => $dateField,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'excludeDemo' => $excludeDemo,
        ]];
    }

    private function safeCsvText(?string $value): ?string
    {
        if ($value !== null && ! is_numeric($value) && preg_match('/^[\x00-\x20]*[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
