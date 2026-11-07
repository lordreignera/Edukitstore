<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
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
            if ($invoice->payment_status === ShoppingList::PAYMENT_PAID
                && (int) $driverId !== (int) $invoice->assigned_driver_id) {
                throw ValidationException::withMessages([
                    'assigned_driver_id' => 'For a paid order, record the packed items and assign the driver together in the fulfilment panel.',
                ]);
            }
            // Keep unpaid or exceptioned orders out of the driver's fulfilment queue.
            if ($driverId && $invoice->payment_status !== ShoppingList::PAYMENT_PAID) {
                throw ValidationException::withMessages([
                    'assigned_driver_id' => 'A driver can only be assigned after the customer payment is verified.',
                ]);
            }
            if ($driverId && (int) $driverId !== (int) $invoice->assigned_driver_id && $invoice->payment_exception) {
                throw ValidationException::withMessages([
                    'assigned_driver_id' => 'Resolve the payment or stock issue before assigning a driver.',
                ]);
            }
            if (($invoice->customer_received_at || $invoice->delivery_confirmed_at) && (int) $driverId !== (int) $invoice->assigned_driver_id) {
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

    /**
     * Record the item check, pack deliverable quantities, and assign the driver as one guarded action.
     *
     * @param  array<int|string, array{unavailable_quantity:int, unavailable_reason?:string|null, unavailable_note?:string|null}>  $items
     */
    public function packAndAssign(
        ShoppingList $order,
        array $items,
        bool $packedItemsConfirmed,
        ?int $driverId,
        int $adminId,
        InventoryService $inventory,
        SupplierSaleService $supplierSales,
    ): ShoppingList {
        return DB::transaction(function () use ($order, $items, $packedItemsConfirmed, $driverId, $adminId, $inventory, $supplierSales): ShoppingList {
            $order = ShoppingList::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $resolvingStockException = $order->payment_exception_type === 'stock'
                && $order->status === ShoppingList::STATUS_REVIEWING;
            if ($order->payment_status !== ShoppingList::PAYMENT_PAID
                || ($order->status !== ShoppingList::STATUS_QUOTED && ! $resolvingStockException)) {
                throw ValidationException::withMessages(['fulfilment' => 'Only a paid, active order can be packed and assigned.']);
            }
            if ($order->customer_received_at || $order->delivery_confirmed_at || $order->driver_started_at || $order->driver_reached_at) {
                throw ValidationException::withMessages(['fulfilment' => 'Packing cannot be changed after the driver has started the delivery.']);
            }
            if ($order->manual_refunded_at) {
                throw ValidationException::withMessages(['fulfilment' => 'Packing is locked after the manual refund has been recorded.']);
            }
            if ($order->payment_exception && $order->payment_exception_type !== 'stock') {
                throw ValidationException::withMessages(['fulfilment' => 'Resolve the payment exception before packing this order.']);
            }

            $lines = $order->lineItems()->lockForUpdate()->get();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['fulfilment' => 'This order has no product lines to pack.']);
            }

            $providedIds = collect(array_keys($items))->map(fn ($id): int => (int) $id)->sort()->values();
            $lineIds = $lines->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values();
            if ($providedIds->all() !== $lineIds->all()) {
                throw ValidationException::withMessages(['items' => 'Refresh this order and review every product line before saving.']);
            }

            $availableCount = 0;
            $packingDetailsChanged = ! $order->packed_at;
            foreach ($lines as $line) {
                $row = $items[$line->id] ?? $items[(string) $line->id];
                $orderedQuantity = (int) ($line->ordered_quantity ?: ($line->quantity + $line->unavailable_quantity));
                $unavailableQuantity = (int) $row['unavailable_quantity'];
                if ($unavailableQuantity > $orderedQuantity) {
                    throw ValidationException::withMessages([
                        "items.{$line->id}.unavailable_quantity" => "Unavailable quantity cannot exceed the {$orderedQuantity} ordered for {$line->product_name}.",
                    ]);
                }

                $reason = $row['unavailable_reason'] ?? null;
                if ($unavailableQuantity > 0 && ! array_key_exists($reason, ShoppingListItem::unavailableReasons())) {
                    throw ValidationException::withMessages([
                        "items.{$line->id}.unavailable_reason" => "Select why {$line->product_name} is unavailable.",
                    ]);
                }

                $availableQuantity = $orderedQuantity - $unavailableQuantity;
                $availableCount += $availableQuantity;
                $nextUnavailableReason = $unavailableQuantity > 0 ? $reason : null;
                $nextUnavailableNote = $unavailableQuantity > 0 ? ($row['unavailable_note'] ?? null) : null;
                $packingDetailsChanged = $packingDetailsChanged
                    || (int) $line->quantity !== $availableQuantity
                    || (int) $line->unavailable_quantity !== $unavailableQuantity
                    || $line->unavailable_reason !== $nextUnavailableReason
                    || $line->unavailable_note !== $nextUnavailableNote;
                $lineTotal = $availableQuantity * (float) $line->unit_price;
                $saleMovement = null;
                $writeoff = null;
                $stockAllocatedQuantity = 0;

                if ($line->fulfilment_source === 'supplier' && $order->packed_at) {
                    $stockAllocatedQuantity = data_get($line->cost_breakdown, '0.stock_allocated_quantity');
                    // Older packed rows have no allocation snapshot. Do not add more than
                    // their currently recorded deliverable quantity.
                    $stockAllocatedQuantity = $stockAllocatedQuantity === null
                        ? (int) $line->quantity
                        : (int) $stockAllocatedQuantity;
                    if ($availableQuantity > $stockAllocatedQuantity) {
                        throw ValidationException::withMessages([
                            "items.{$line->id}.unavailable_quantity" => 'These additional units were not reserved from supplier stock. Restock them and create a new order before adding them to this delivery.',
                        ]);
                    }
                } elseif ($line->fulfilment_source !== 'supplier' && $line->product_id) {
                    $saleMovement = InventoryMovement::query()
                        ->where('shopping_list_id', $order->id)
                        ->where('product_id', $line->product_id)
                        ->where('type', InventoryMovement::TYPE_SALE_PAID)
                        ->lockForUpdate()
                        ->first();
                    $writeoff = InventoryMovement::query()
                        ->where('shopping_list_id', $order->id)
                        ->where('product_id', $line->product_id)
                        ->where('type', InventoryMovement::TYPE_UNAVAILABLE_STOCK_WRITEOFF)
                        ->lockForUpdate()
                        ->first();
                    $stockAllocatedQuantity = $saleMovement
                        ? (int) $saleMovement->quantity + (int) ($writeoff?->quantity ?? 0)
                        : 0;

                    if ($order->packed_at && $availableQuantity > $stockAllocatedQuantity) {
                        throw ValidationException::withMessages([
                            "items.{$line->id}.unavailable_quantity" => 'These additional units were not allocated from display stock. Restock them and create a new order before adding them to this delivery.',
                        ]);
                    }
                }

                $supplierCost = $line->fulfilment_source === 'supplier'
                    ? ($order->payment_exception_type === 'stock' ? 0 : $availableQuantity * (float) $line->unit_cost)
                    : (float) $line->cost_total;

                $line->forceFill([
                    'ordered_quantity' => $orderedQuantity,
                    'quantity' => $availableQuantity,
                    'unavailable_quantity' => $unavailableQuantity,
                    'unavailable_reason' => $unavailableQuantity > 0 ? $reason : null,
                    'unavailable_note' => $unavailableQuantity > 0 ? ($row['unavailable_note'] ?? null) : null,
                    'line_total' => $lineTotal,
                    // Supplier payables are earned only for units actually delivered; EduKit-owned
                    // stock costs remain recorded if those units were already consumed at payment.
                    ...($line->fulfilment_source === 'supplier' ? [
                        'cost_total' => $supplierCost,
                        'supplier_payable' => $supplierCost,
                    ] : []),
                    'profit_total' => $lineTotal - $supplierCost,
                ])->save();

                if ($line->fulfilment_source !== 'supplier' && $line->product_id) {
                    // Separate delivered units from any costed stock that cannot be delivered.
                    if ($saleMovement) {
                        $stockWriteoffQuantity = min(
                            $unavailableQuantity,
                            max(0, $stockAllocatedQuantity - $availableQuantity),
                        );
                        [$deliveredCost, $unavailableCost, $deliveredBreakdown, $unavailableBreakdown] = $this->splitInventoryCost(
                            $line,
                            $availableQuantity,
                            $stockWriteoffQuantity,
                            $stockAllocatedQuantity,
                        );

                        $saleMeta = $saleMovement->meta ?? [];
                        $saleMeta['ordered_quantity'] = $orderedQuantity;
                        $saleMeta['delivered_quantity'] = $availableQuantity;
                        $saleMeta['cost_breakdown'] = $deliveredBreakdown;

                        $saleMovement->forceFill([
                            'quantity' => $availableQuantity,
                            'display_quantity_delta' => -$availableQuantity,
                            'unit_cost' => $availableQuantity > 0 ? $deliveredCost / $availableQuantity : 0,
                            'unit_price' => $line->unit_price,
                            'total_cost' => $deliveredCost,
                            'total_revenue' => $lineTotal,
                            'profit' => $lineTotal - $deliveredCost,
                            'meta' => $saleMeta,
                        ])->save();

                        if ($stockWriteoffQuantity > 0) {
                            $writeoffMeta = [
                                'customer' => $order->parent_name,
                                'phone' => $order->phone,
                                'school' => $order->school_name,
                                'ordered_quantity' => $orderedQuantity,
                                'delivered_quantity' => $availableQuantity,
                                'unavailable_quantity' => $unavailableQuantity,
                                'stock_writeoff_quantity' => $stockWriteoffQuantity,
                                'unavailable_reason' => $reason,
                                'unavailable_note' => $row['unavailable_note'] ?? null,
                                'cost_breakdown' => $unavailableBreakdown,
                            ];
                            $writeoffData = [
                                'product_id' => $line->product_id,
                                'shopping_list_id' => $order->id,
                                'performed_by' => $adminId,
                                'type' => InventoryMovement::TYPE_UNAVAILABLE_STOCK_WRITEOFF,
                                'reference' => $order->reference,
                                'product_name' => $line->product_name,
                                'sku' => $line->sku,
                                'quantity' => $stockWriteoffQuantity,
                                // Split the original paid-sale stock delta into delivered and written-off
                                // quantities; together they still equal the one deduction at payment.
                                'warehouse_quantity_delta' => 0,
                                'display_quantity_delta' => -$stockWriteoffQuantity,
                                'unit_cost' => $unavailableCost / $stockWriteoffQuantity,
                                'unit_price' => 0,
                                'total_cost' => $unavailableCost,
                                'total_revenue' => 0,
                                'profit' => -$unavailableCost,
                                'from_location' => 'website_display',
                                'to_location' => 'unavailable_stock_writeoff',
                                'notes' => 'Shortage identified during packing; this splits the original payment stock deduction and does not deduct stock a second time.',
                                'meta' => $writeoffMeta,
                                'occurred_at' => $saleMovement->occurred_at,
                            ];

                            if ($writeoff) {
                                $writeoff->forceFill($writeoffData)->save();
                            } else {
                                InventoryMovement::create($writeoffData);
                            }
                        } elseif ($writeoff) {
                            // Packing may be corrected before dispatch; remove a now-resolved loss row.
                            $writeoff->delete();
                        }
                    }
                }
            }

            if ($availableCount > 0 && ! $packedItemsConfirmed) {
                throw ValidationException::withMessages(['packed_items_confirmed' => 'Confirm the available products have been packed before assigning a driver.']);
            }
            if ($availableCount > 0 && ! $driverId) {
                throw ValidationException::withMessages(['assigned_driver_id' => 'Choose an approved, available driver for the products being delivered.']);
            }
            if ($availableCount === 0 && $driverId) {
                throw ValidationException::withMessages(['assigned_driver_id' => 'No driver is needed when every product is unavailable. Record the manual refund instead.']);
            }
            if ($driverId && (int) $driverId !== (int) $order->assigned_driver_id
                && ! Driver::whereKey($driverId)->where('is_approved', true)->where('is_available', true)->exists()) {
                throw ValidationException::withMessages(['assigned_driver_id' => 'Choose an approved and available driver.']);
            }

            // A repeated POST with the exact saved quantities and driver is a no-op:
            // preserve the original packed timestamp and avoid replaying fulfilment side effects.
            if (! $packingDetailsChanged
                && ! $resolvingStockException
                && (int) $driverId === (int) $order->assigned_driver_id) {
                return $order->fresh(['assignedDriver', 'lineItems.product']);
            }

            if ($order->payment_exception_type === 'stock') {
                $inventory->recordPaidCartSale($order);
                $supplierSales->recordPaidSale($order);
            }

            $order->forceFill([
                'packed_at' => now(),
                'packed_by' => $adminId,
                'assigned_driver_id' => $driverId,
                'status' => $resolvingStockException ? ShoppingList::STATUS_QUOTED : $order->status,
                'payment_exception' => $order->payment_exception_type === 'stock' ? null : $order->payment_exception,
                'payment_exception_type' => $order->payment_exception_type === 'stock' ? null : $order->payment_exception_type,
            ])->save();

            return $order->fresh(['assignedDriver', 'lineItems.product']);
        });
    }

    public function recordManualRefund(ShoppingList $order, int $adminId, string $reference): ShoppingList
    {
        return DB::transaction(function () use ($order, $adminId, $reference): ShoppingList {
            $order = ShoppingList::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->payment_status !== ShoppingList::PAYMENT_PAID || ! $order->packed_at) {
                throw ValidationException::withMessages(['refund' => 'Record a refund only for a paid order after its unavailable items have been saved.']);
            }
            if ($order->manual_refunded_at) {
                throw ValidationException::withMessages(['refund' => 'A manual refund has already been recorded for this order.']);
            }

            $amount = $order->manualRefundDue();
            if ($amount < 1) {
                throw ValidationException::withMessages(['refund' => 'There is no unavailable-item refund due on this order.']);
            }

            $order->forceFill([
                'manual_refund_amount' => $amount,
                'manual_refund_reference' => $reference,
                'manual_refunded_at' => now(),
                'manual_refund_recorded_by' => $adminId,
                'payment_status' => $order->allItemsUnavailable() ? ShoppingList::PAYMENT_REFUNDED : ShoppingList::PAYMENT_PAID,
                'status' => $order->allItemsUnavailable() ? ShoppingList::STATUS_CANCELLED : $order->status,
            ])->save();

            return $order->fresh(['customer', 'lineItems']);
        });
    }

    private function validateTransition(ShoppingList $invoice, string $nextStatus): void
    {
        if ($invoice->payment_status === ShoppingList::PAYMENT_PAID && $nextStatus !== $invoice->status) {
            throw ValidationException::withMessages(['status' => 'Paid-order stages are managed through packing, delivery confirmation, and the refund workflow.']);
        }

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

    /**
     * Split the original FIFO batch costs between delivered units and unavailable units.
     * If older order lines have no batch breakdown, allocate their recorded cost proportionally.
     *
     * @return array{0:float, 1:float, 2:array<int, array<string, mixed>>, 3:array<int, array<string, mixed>>}
     */
    private function splitInventoryCost(ShoppingListItem $line, int $deliveredQuantity, int $unavailableQuantity, int $orderedQuantity): array
    {
        $deliveredBreakdown = [];
        $unavailableBreakdown = [];
        $deliveredCost = 0.0;
        $unavailableCost = 0.0;
        $remainingDelivered = $deliveredQuantity;
        $remainingUnavailable = $unavailableQuantity;

        foreach ($line->cost_breakdown ?? [] as $batch) {
            $batchQuantity = max(0, (int) ($batch['quantity'] ?? 0));
            if ($batchQuantity === 0) {
                continue;
            }

            $unitCost = (float) ($batch['unit_cost'] ?? 0);
            if (array_key_exists('cost_total', $batch)) {
                $unitCost = (float) $batch['cost_total'] / $batchQuantity;
            }

            $deliveredFromBatch = min($batchQuantity, $remainingDelivered);
            if ($deliveredFromBatch > 0) {
                $cost = round($deliveredFromBatch * $unitCost, 2);
                $deliveredCost += $cost;
                $deliveredBreakdown[] = array_merge($batch, ['quantity' => $deliveredFromBatch, 'cost_total' => $cost]);
                $remainingDelivered -= $deliveredFromBatch;
            }

            $unavailableFromBatch = min($batchQuantity - $deliveredFromBatch, $remainingUnavailable);
            if ($unavailableFromBatch > 0) {
                $cost = round($unavailableFromBatch * $unitCost, 2);
                $unavailableCost += $cost;
                $unavailableBreakdown[] = array_merge($batch, ['quantity' => $unavailableFromBatch, 'cost_total' => $cost]);
                $remainingUnavailable -= $unavailableFromBatch;
            }
        }

        $unallocatedQuantity = $remainingDelivered + $remainingUnavailable;
        if ($unallocatedQuantity > 0) {
            $remainingCost = max(0, (float) $line->cost_total - $deliveredCost - $unavailableCost);
            $fallbackUnitCost = $unallocatedQuantity > 0 ? $remainingCost / $unallocatedQuantity : 0;

            if ($remainingDelivered > 0) {
                $cost = round($remainingDelivered * $fallbackUnitCost, 2);
                $deliveredCost += $cost;
                $deliveredBreakdown[] = ['quantity' => $remainingDelivered, 'unit_cost' => $fallbackUnitCost, 'cost_total' => $cost];
            }
            if ($remainingUnavailable > 0) {
                $cost = round($remainingCost - ($remainingDelivered * $fallbackUnitCost), 2);
                $unavailableCost += $cost;
                $unavailableBreakdown[] = ['quantity' => $remainingUnavailable, 'unit_cost' => $fallbackUnitCost, 'cost_total' => $cost];
            }
        }

        return [round($deliveredCost, 2), round($unavailableCost, 2), $deliveredBreakdown, $unavailableBreakdown];
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
