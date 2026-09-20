<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\ShoppingList;
use App\Services\InvoiceWorkflow;
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
            ->when($driverId !== '', fn ($query) => $query->where('assigned_driver_id', $driverId))
            ->when($deliveryStatus === 'unassigned', fn ($query) => $query->whereNull('assigned_driver_id')->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'awaiting_payment', fn ($query) => $query->whereNotNull('assigned_driver_id')->where('payment_status', '!=', ShoppingList::PAYMENT_PAID)->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'ready_for_delivery', fn ($query) => $query->whereNotNull('assigned_driver_id')->where('payment_status', ShoppingList::PAYMENT_PAID)->whereNull('delivery_confirmed_at'))
            ->when($deliveryStatus === 'delivered', fn ($query) => $query->whereNotNull('delivery_confirmed_at'))
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
        ]);
    }

    public function show(ShoppingList $invoice): View
    {
        return view('admin.invoices.show', [
            'invoice' => $invoice->load('assignedDriver', 'deliveryConfirmer', 'school.district', 'lineItems'),
            'statuses' => ShoppingList::statuses(),
            'drivers' => Driver::query()
                ->where('is_approved', true)
                ->where('is_available', true)
                ->orderBy('name')
                ->get(),
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

        $workflow->updateFromAdmin($invoice, $data, auth()->id());

        return back()->with('status', 'Invoice updated.');
    }
}
