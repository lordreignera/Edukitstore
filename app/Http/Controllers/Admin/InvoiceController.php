<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\ShoppingList;
use App\Models\Product;
use App\Services\InvoiceWorkflow;
use App\Services\InventoryService;
use App\Services\SupplierSaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');
        $paymentStatus = (string) $request->query('payment_status');
        $deliveryStatus = (string) $request->query('delivery_status');
        $driverId = (string) $request->query('driver_id');
        $source = (string) $request->query('source');
        $issuesOnly = $request->boolean('issues');

        $invoices = ShoppingList::query()
            ->with('assignedDriver', 'school.district')
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
            ->when($issuesOnly, fn ($query) => $query->whereNotNull('payment_exception'))
            ->when($driverId !== '', fn ($query) => $query->where('assigned_driver_id', $driverId))
            ->when($deliveryStatus === 'unassigned', fn ($query) => $query->whereNull('assigned_driver_id')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'awaiting_payment', fn ($query) => $query->whereNotNull('assigned_driver_id')->where('payment_status', '!=', ShoppingList::PAYMENT_PAID)->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'ready_for_delivery', fn ($query) => $query->whereNotNull('assigned_driver_id')->where('payment_status', ShoppingList::PAYMENT_PAID)->whereNull('payment_exception')->whereNull('driver_started_at')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'in_transit', fn ($query) => $query->whereNotNull('driver_started_at')->whereNull('driver_reached_at')->whereNull('customer_received_at'))
            ->when($deliveryStatus === 'awaiting_customer_confirmation', fn ($query) => $query->whereNotNull('driver_reached_at')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'under_review', fn ($query) => $query->whereNotNull('payment_exception')->whereNull('customer_received_at')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'delivered', fn ($query) => $query->where(function ($statusQuery) {
                $statusQuery->whereNotNull('customer_received_at')->orWhereNotNull('delivery_confirmed_at');
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'drivers' => Driver::where('is_approved', true)->orderBy('name')->get(),
            'statuses' => ShoppingList::statuses(),
            'paymentStatuses' => ShoppingList::paymentStatuses(),
            'deliveryStatuses' => ShoppingList::deliveryStatuses(),
            'search' => $search,
            'selectedStatus' => $status,
            'selectedPaymentStatus' => $paymentStatus,
            'selectedDeliveryStatus' => $deliveryStatus,
            'selectedDriverId' => $driverId,
            'selectedSource' => $source,
            'issuesOnly' => $issuesOnly,
        ]);
    }

    public function show(ShoppingList $invoice): View
    {
        return view('admin.invoices.show', [
            'invoice' => $invoice->load('assignedDriver', 'deliveryConfirmer', 'driverStartedBy', 'driverReachedBy', 'school.district', 'lineItems', 'paymentAttempts'),
            'statuses' => ShoppingList::statuses(),
            'drivers' => Driver::query()
                ->where('is_approved', true)
                ->where('is_available', true)
                ->orderBy('name')
                ->get(),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'sku', 'price']),
        ]);
    }

    public function update(Request $request, ShoppingList $invoice, InvoiceWorkflow $workflow): RedirectResponse
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

        $workflow->updateFromAdmin($invoice, $data, auth()->id());

        return back()->with('status', 'Invoice updated.');
    }

    public function resolvePaidStock(ShoppingList $invoice, InvoiceWorkflow $workflow, InventoryService $inventory, SupplierSaleService $supplierSales): RedirectResponse
    {
        $workflow->resolvePaidStock($invoice, $inventory, $supplierSales);
        return back()->with('status', 'Stock allocated. The paid order is ready for delivery.');
    }
}
