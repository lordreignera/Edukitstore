<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Product;
use App\Models\ShoppingList;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceWorkflow
{
    public function resolvePaidStock(ShoppingList $invoice, InventoryService $inventory, SupplierSaleService $supplierSales): void
    {
        DB::transaction(function () use ($invoice, $inventory, $supplierSales): void {
            $invoice = ShoppingList::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->payment_status !== ShoppingList::PAYMENT_PAID || $invoice->payment_exception_type !== 'stock') {
                throw ValidationException::withMessages(['payment' => 'This invoice does not have a stock exception to resolve.']);
            }

            $inventory->recordPaidCartSale($invoice);
            $supplierSales->recordPaidSale($invoice);
            $invoice->update([
                'payment_exception' => null,
                'payment_exception_type' => null,
                'status' => ShoppingList::STATUS_QUOTED,
            ]);
        });
    }

    public function updateFromAdmin(ShoppingList $invoice, array $data, int $adminId): ShoppingList
    {
        return DB::transaction(function () use ($invoice, $data, $adminId): ShoppingList {
            $invoice = ShoppingList::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $nextStatus = $data['status'];
            $this->validateTransition($invoice, $nextStatus);

            $driverId = array_key_exists('assigned_driver_id', $data)
                ? $data['assigned_driver_id']
                : $invoice->assigned_driver_id;
            if ($invoice->delivery_confirmed_at && (int) $driverId !== (int) $invoice->assigned_driver_id) {
                throw ValidationException::withMessages(['assigned_driver_id' => 'A completed delivery cannot be reassigned.']);
            }
            if ($driverId && (int) $driverId !== (int) $invoice->assigned_driver_id
                && ! Driver::whereKey($driverId)->where('is_approved', true)->where('is_available', true)->exists()) {
                throw ValidationException::withMessages(['assigned_driver_id' => 'Choose an approved and available driver.']);
            }

            if ($invoice->source === ShoppingList::SOURCE_UPLOAD && array_key_exists('items', $data)) {
                if ($invoice->status === ShoppingList::STATUS_QUOTED || $invoice->payment_status !== ShoppingList::PAYMENT_UNPAID) {
                    throw ValidationException::withMessages(['items' => 'Invoice items cannot change after the quote is released or payment starts.']);
                }
                $this->replaceUploadedItems($invoice, $data['items']);
            }

            if ($invoice->source === ShoppingList::SOURCE_UPLOAD && $nextStatus === ShoppingList::STATUS_QUOTED && $invoice->lineItems()->count() === 0) {
                throw ValidationException::withMessages(['items' => 'Add at least one priced product before releasing this invoice.']);
            }

            $fee = $invoice->source === ShoppingList::SOURCE_CART
                ? (int) $invoice->delivery_fee
                : (int) ($data['delivery_fee'] ?? $invoice->delivery_fee ?? 0);
            if ($invoice->payment_status !== ShoppingList::PAYMENT_UNPAID && $fee !== (int) $invoice->delivery_fee) {
                throw ValidationException::withMessages(['delivery_fee' => 'The delivery fee is locked after payment starts.']);
            }
            $total = $invoice->source === ShoppingList::SOURCE_CART
                ? (int) $invoice->items_subtotal + $fee
                : ($invoice->lineItems()->exists() ? (int) $invoice->items_subtotal + $fee : null);

            $invoice->update([
                'status' => $nextStatus,
                'assigned_driver_id' => $driverId,
                'delivery_fee' => $fee,
                'estimated_total' => $total,
                'reviewed_at' => now(),
                'reviewed_by' => $adminId,
            ]);

            return $invoice->refresh();
        });
    }

    private function validateTransition(ShoppingList $invoice, string $nextStatus): void
    {
        $allowed = [
            ShoppingList::STATUS_PENDING => [ShoppingList::STATUS_PENDING, ShoppingList::STATUS_REVIEWING, ShoppingList::STATUS_QUOTED, ShoppingList::STATUS_REJECTED, ShoppingList::STATUS_CANCELLED],
            ShoppingList::STATUS_REVIEWING => [ShoppingList::STATUS_REVIEWING, ShoppingList::STATUS_QUOTED, ShoppingList::STATUS_REJECTED, ShoppingList::STATUS_CANCELLED],
            ShoppingList::STATUS_QUOTED => [ShoppingList::STATUS_QUOTED, ShoppingList::STATUS_REVIEWING, ShoppingList::STATUS_CANCELLED],
            ShoppingList::STATUS_REJECTED => [ShoppingList::STATUS_REJECTED],
            ShoppingList::STATUS_CANCELLED => [ShoppingList::STATUS_CANCELLED],
            ShoppingList::STATUS_FULFILLED => [ShoppingList::STATUS_FULFILLED],
        ];

        if (! in_array($nextStatus, $allowed[$invoice->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'This status change is not allowed.']);
        }
        if ($nextStatus === ShoppingList::STATUS_FULFILLED && ! $invoice->delivery_confirmed_at) {
            throw ValidationException::withMessages(['status' => 'Only the assigned driver can confirm delivery.']);
        }
        if ($invoice->payment_status === ShoppingList::PAYMENT_PAID
            && in_array($nextStatus, [ShoppingList::STATUS_CANCELLED, ShoppingList::STATUS_REJECTED], true)) {
            throw ValidationException::withMessages(['status' => 'Paid invoices require a recorded refund before cancellation.']);
        }
        if ($invoice->payment_status === ShoppingList::PAYMENT_PENDING && $nextStatus !== $invoice->status) {
            throw ValidationException::withMessages(['status' => 'Wait for payment verification before changing this status.']);
        }
        if ($invoice->payment_exception && $nextStatus === ShoppingList::STATUS_QUOTED) {
            throw ValidationException::withMessages(['status' => 'Resolve the payment exception before releasing this order.']);
        }
    }

    private function replaceUploadedItems(ShoppingList $invoice, array $rows): void
    {
        $items = collect($rows)->filter(fn ($row) => ! empty($row['product_id']))->values();
        $products = Product::whereIn('id', $items->pluck('product_id'))->get()->keyBy('id');
        $snapshots = $items->map(function ($row) use ($products): array {
            $product = $products->get((int) $row['product_id']);
            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages(['items' => 'Choose an active catalogue product for each line.']);
            }
            $quantity = (int) $row['quantity'];
            $price = (int) $row['unit_price'];
            return [
                'product_id' => $product->id, 'name' => $product->name, 'sku' => $product->sku,
                'quantity' => $quantity, 'unit_cost' => (int) $product->cost_price,
                'unit_price' => $price, 'line_total' => $quantity * $price,
                'fulfilment_source' => 'edukit',
            ];
        });

        if ($snapshots->pluck('product_id')->unique()->count() !== $snapshots->count()) {
            throw ValidationException::withMessages(['items' => 'Enter each product only once.']);
        }

        $invoice->lineItems()->delete();
        $invoice->lineItems()->createMany($snapshots->map(fn ($item) => [
            'product_id' => $item['product_id'], 'fulfilment_source' => 'edukit',
            'product_name' => $item['name'], 'sku' => $item['sku'],
            'quantity' => $item['quantity'], 'unit_cost' => $item['unit_cost'],
            'unit_price' => $item['unit_price'], 'line_total' => $item['line_total'],
        ])->all());
        $invoice->update(['cart_items' => $snapshots->all(), 'items_subtotal' => $snapshots->sum('line_total')]);
    }
}
