<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function recordOpeningStock(Product $product, array $data, ?int $userId = null): InventoryMovement
    {
        return DB::transaction(function () use ($product, $data, $userId): InventoryMovement {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($lockedProduct->inventoryBatches()->where('source', InventoryBatch::SOURCE_OPENING_STOCK)->exists()) {
                throw ValidationException::withMessages([
                    'opening_stock' => 'Opening stock has already been recorded for this product. Edit that movement instead.',
                ]);
            }

            $warehouseQuantity = (int) ($data['warehouse_quantity'] ?? 0);
            $displayQuantity = (int) ($data['display_quantity'] ?? 0);
            $quantity = $warehouseQuantity + $displayQuantity;

            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    'opening_stock' => 'Enter warehouse stock, display stock, or both.',
                ]);
            }

            $unitCost = (float) $data['unit_cost'];
            $unitPrice = (float) ($data['unit_price'] ?? $lockedProduct->price);
            $occurredAt = $this->parseDate($data['occurred_at'] ?? $data['stock_date'] ?? null);

            $batch = $this->createBatch($lockedProduct, [
                'created_by' => $userId,
                'source' => InventoryBatch::SOURCE_OPENING_STOCK,
                'received_at' => $occurredAt->toDateString(),
                'quantity_received' => $quantity,
                'remaining_quantity' => $quantity,
                'warehouse_remaining_quantity' => $warehouseQuantity,
                'display_remaining_quantity' => $displayQuantity,
                'unit_cost' => $unitCost,
                'unit_price' => $unitPrice,
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedProduct->forceFill([
                'cost_price' => $unitCost,
                'price' => $unitPrice,
                'warehouse_stock_quantity' => $lockedProduct->warehouse_stock_quantity + $warehouseQuantity,
                'stock_quantity' => $lockedProduct->stock_quantity + $displayQuantity,
            ])->save();

            return $this->createMovement($lockedProduct, [
                'inventory_batch_id' => $batch->id,
                'performed_by' => $userId,
                'type' => InventoryMovement::TYPE_OPENING_STOCK,
                'quantity' => $quantity,
                'warehouse_quantity_delta' => $warehouseQuantity,
                'display_quantity_delta' => $displayQuantity,
                'unit_cost' => $unitCost,
                'unit_price' => $unitPrice,
                'total_cost' => $quantity * $unitCost,
                'total_revenue' => 0,
                'profit' => 0,
                'from_location' => 'opening_balance',
                'to_location' => 'warehouse_and_display',
                'notes' => $data['notes'] ?? null,
                'occurred_at' => $occurredAt,
            ]);
        });
    }

    public function recordIntake(Product $product, array $data, ?int $userId = null): InventoryMovement
    {
        return DB::transaction(function () use ($product, $data, $userId): InventoryMovement {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $quantity = (int) $data['quantity'];
            $unitCost = (float) $data['unit_cost'];
            $unitPrice = (float) ($data['unit_price'] ?? $lockedProduct->price);
            $occurredAt = $this->parseDate($data['occurred_at'] ?? null);

            $batch = $this->createBatch($lockedProduct, [
                'created_by' => $userId,
                'source' => InventoryBatch::SOURCE_STOCK_INTAKE,
                'received_at' => $occurredAt->toDateString(),
                'quantity_received' => $quantity,
                'remaining_quantity' => $quantity,
                'warehouse_remaining_quantity' => $quantity,
                'display_remaining_quantity' => 0,
                'unit_cost' => $unitCost,
                'unit_price' => $unitPrice,
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedProduct->forceFill([
                'cost_price' => $unitCost,
                'price' => $unitPrice,
                'warehouse_stock_quantity' => $lockedProduct->warehouse_stock_quantity + $quantity,
            ])->save();

            return $this->createMovement($lockedProduct, [
                'inventory_batch_id' => $batch->id,
                'performed_by' => $userId,
                'type' => InventoryMovement::TYPE_STOCK_INTAKE,
                'quantity' => $quantity,
                'warehouse_quantity_delta' => $quantity,
                'display_quantity_delta' => 0,
                'unit_cost' => $unitCost,
                'unit_price' => $unitPrice,
                'total_cost' => $quantity * $unitCost,
                'total_revenue' => 0,
                'profit' => 0,
                'from_location' => 'supplier',
                'to_location' => 'warehouse',
                'notes' => $data['notes'] ?? null,
                'occurred_at' => $occurredAt,
            ]);
        });
    }

    public function transferToDisplay(Product $product, int $quantity, ?int $userId = null, ?string $notes = null, mixed $occurredAt = null): InventoryMovement
    {
        return DB::transaction(function () use ($product, $quantity, $userId, $notes, $occurredAt): InventoryMovement {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'Enter at least one unit to move.',
                ]);
            }

            if ($lockedProduct->warehouse_stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'The warehouse does not have enough stock to move this product to the website.',
                ]);
            }

            $allocation = $this->allocateTransferBatches($lockedProduct, $quantity);

            $lockedProduct->forceFill([
                'warehouse_stock_quantity' => $lockedProduct->warehouse_stock_quantity - $quantity,
                'stock_quantity' => $lockedProduct->stock_quantity + $quantity,
            ])->save();
            $averageUnitCost = $quantity > 0 ? $allocation['total_cost'] / $quantity : 0;

            return $this->createMovement($lockedProduct, [
                'inventory_batch_id' => $allocation['single_batch_id'],
                'performed_by' => $userId,
                'type' => InventoryMovement::TYPE_TRANSFER_TO_DISPLAY,
                'quantity' => $quantity,
                'warehouse_quantity_delta' => -$quantity,
                'display_quantity_delta' => $quantity,
                'unit_cost' => $averageUnitCost,
                'unit_price' => (float) $lockedProduct->price,
                'total_cost' => $allocation['total_cost'],
                'total_revenue' => 0,
                'profit' => 0,
                'from_location' => 'warehouse',
                'to_location' => 'website_display',
                'notes' => $notes,
                'meta' => [
                    'transfer_breakdown' => $allocation['breakdown'],
                ],
                'occurred_at' => $this->parseDate($occurredAt),
            ]);
        });
    }

    public function ensureInvoiceHasDisplayStock(ShoppingList $shoppingList): void
    {
        foreach ($this->cartProducts($shoppingList) as $line) {
            $product = $line['product'];
            $quantity = $line['quantity'];

            if (! $product->is_active || $product->stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => "{$product->name} has only {$product->stock_quantity} available for sale.",
                ]);
            }

            if ($product->inventoryBatches()->sum('display_remaining_quantity') < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => "{$product->name} needs costed display stock before payment can be accepted.",
                ]);
            }
        }
    }

    public function recordPaidCartSale(ShoppingList $shoppingList, ?int $userId = null): void
    {
        if ($shoppingList->source !== ShoppingList::SOURCE_CART || empty($shoppingList->cart_items)) {
            return;
        }

        DB::transaction(function () use ($shoppingList, $userId): void {
            $lockedInvoice = ShoppingList::whereKey($shoppingList->id)->lockForUpdate()->firstOrFail();

            if (InventoryMovement::where('shopping_list_id', $lockedInvoice->id)
                ->where('type', InventoryMovement::TYPE_SALE_PAID)
                ->exists()) {
                return;
            }

            foreach ($this->cartProducts($lockedInvoice, lockProducts: true) as $line) {
                $product = $line['product'];
                $item = $line['item'];
                $quantity = $line['quantity'];

                if ($product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'payment' => "{$product->name} has only {$product->stock_quantity} available for sale.",
                    ]);
                }

                $unitPrice = (float) ($item['unit_price'] ?? $product->price);
                $costing = $this->consumeCostBatches($product, $quantity);
                $totalRevenue = $quantity * $unitPrice;
                $averageUnitCost = $quantity > 0 ? $costing['total_cost'] / $quantity : 0;

                $product->forceFill([
                    'stock_quantity' => $product->stock_quantity - $quantity,
                ])->save();

                ShoppingListItem::updateOrCreate(
                    [
                        'shopping_list_id' => $lockedInvoice->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'product_name' => $product->name,
                        'sku' => $product->sku,
                        'quantity' => $quantity,
                        'unit_cost' => $averageUnitCost,
                        'unit_price' => $unitPrice,
                        'line_total' => $totalRevenue,
                        'cost_total' => $costing['total_cost'],
                        'profit_total' => $totalRevenue - $costing['total_cost'],
                        'cost_breakdown' => $costing['breakdown'],
                    ]
                );

                $this->createMovement($product, [
                    'shopping_list_id' => $lockedInvoice->id,
                    'performed_by' => $userId,
                    'type' => InventoryMovement::TYPE_SALE_PAID,
                    'reference' => $lockedInvoice->reference,
                    'quantity' => $quantity,
                    'warehouse_quantity_delta' => 0,
                    'display_quantity_delta' => -$quantity,
                    'unit_cost' => $averageUnitCost,
                    'unit_price' => $unitPrice,
                    'total_cost' => $costing['total_cost'],
                    'total_revenue' => $totalRevenue,
                    'profit' => $totalRevenue - $costing['total_cost'],
                    'from_location' => 'website_display',
                    'to_location' => 'customer_order',
                    'notes' => 'Stock deducted after verified payment.',
                    'meta' => [
                        'customer' => $lockedInvoice->parent_name,
                        'phone' => $lockedInvoice->phone,
                        'school' => $lockedInvoice->school_name,
                        'cost_breakdown' => $costing['breakdown'],
                    ],
                ]);
            }
        });
    }

    public function updateMovement(InventoryMovement $movement, array $data, ?int $userId = null): InventoryMovement
    {
        return DB::transaction(function () use ($movement, $data, $userId): InventoryMovement {
            $lockedMovement = InventoryMovement::with('inventoryBatch')
                ->whereKey($movement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedMovement->isEditable()) {
                throw ValidationException::withMessages([
                    'movement' => 'Paid sales are locked because they are tied to customer payments.',
                ]);
            }

            return match ($lockedMovement->type) {
                InventoryMovement::TYPE_OPENING_STOCK => $this->updateOpeningMovement($lockedMovement, $data, $userId),
                InventoryMovement::TYPE_STOCK_INTAKE => $this->updateIntakeMovement($lockedMovement, $data, $userId),
                InventoryMovement::TYPE_TRANSFER_TO_DISPLAY => $this->updateTransferMovement($lockedMovement, $data, $userId),
                default => $lockedMovement,
            };
        });
    }

    public function deleteMovement(InventoryMovement $movement): void
    {
        DB::transaction(function () use ($movement): void {
            $lockedMovement = InventoryMovement::with('inventoryBatch')
                ->whereKey($movement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedMovement->isEditable()) {
                throw ValidationException::withMessages([
                    'movement' => 'Paid sales cannot be deleted from the stock ledger.',
                ]);
            }

            $product = Product::whereKey($lockedMovement->product_id)->lockForUpdate()->firstOrFail();

            if ($lockedMovement->type === InventoryMovement::TYPE_TRANSFER_TO_DISPLAY) {
                $quantity = (int) $lockedMovement->quantity;

                $this->reverseTransferBatches($lockedMovement);
                $this->applyProductDeltas($product, $quantity, -$quantity);

                $lockedMovement->delete();

                return;
            }

            $batch = $this->lockedBatch($lockedMovement);

            if ($batch->consumedQuantity() > 0) {
                throw ValidationException::withMessages([
                    'movement' => 'This stock record cannot be deleted because some of the batch has already been sold.',
                ]);
            }

            $warehouseDelta = (int) $lockedMovement->warehouse_quantity_delta;
            $displayDelta = (int) $lockedMovement->display_quantity_delta;

            if ($batch->warehouse_remaining_quantity < $warehouseDelta || $batch->display_remaining_quantity < $displayDelta) {
                throw ValidationException::withMessages([
                    'movement' => 'This stock record cannot be deleted because some of its batch has already moved.',
                ]);
            }

            if ($warehouseDelta > 0 && $product->warehouse_stock_quantity < $warehouseDelta) {
                throw ValidationException::withMessages([
                    'movement' => 'Warehouse stock has moved since this record. Adjust later movements first.',
                ]);
            }

            if ($displayDelta > 0 && $product->stock_quantity < $displayDelta) {
                throw ValidationException::withMessages([
                    'movement' => 'Display stock has moved since this record. Adjust later movements first.',
                ]);
            }

            $product->forceFill([
                'warehouse_stock_quantity' => $product->warehouse_stock_quantity - $warehouseDelta,
                'stock_quantity' => $product->stock_quantity - $displayDelta,
            ])->save();

            $lockedMovement->delete();
            $batch->delete();
            $this->refreshCurrentPrices($product);
        });
    }

    public function stockBalanceBefore(mixed $date): int
    {
        $before = $this->parseDate($date)->startOfDay();

        return (int) ($this->stockBalanceQuery()
            ->where('occurred_at', '<', $before)
            ->first()
            ->balance ?? 0);
    }

    public function stockBalanceUpTo(mixed $date): int
    {
        $end = $this->parseDate($date)->endOfDay();

        return (int) ($this->stockBalanceQuery()
            ->where('occurred_at', '<=', $end)
            ->first()
            ->balance ?? 0);
    }

    private function updateOpeningMovement(InventoryMovement $movement, array $data, ?int $userId): InventoryMovement
    {
        $product = Product::whereKey($movement->product_id)->lockForUpdate()->firstOrFail();
        $batch = $this->lockedBatch($movement);

        $warehouseQuantity = (int) ($data['warehouse_quantity'] ?? 0);
        $displayQuantity = (int) ($data['display_quantity'] ?? 0);
        $quantity = $warehouseQuantity + $displayQuantity;

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'opening_stock' => 'Opening stock needs at least one unit.',
            ]);
        }

        $warehouseDelta = $warehouseQuantity - $movement->warehouse_quantity_delta;
        $displayDelta = $displayQuantity - $movement->display_quantity_delta;

        $this->ensureBatchLocationCanAdjust($batch, $warehouseDelta, $displayDelta);
        $this->applyProductDeltas($product, $warehouseDelta, $displayDelta);

        $unitCost = (float) $data['unit_cost'];
        $unitPrice = (float) $data['unit_price'];
        $occurredAt = $this->parseDate($data['occurred_at'] ?? null);

        $this->updateBatch($batch, $warehouseDelta + $displayDelta, $warehouseDelta, $displayDelta, $unitCost, $unitPrice, $occurredAt, $data['notes'] ?? null);

        $movement->forceFill([
            'performed_by' => $userId ?? $movement->performed_by,
            'quantity' => $quantity,
            'warehouse_quantity_delta' => $warehouseQuantity,
            'display_quantity_delta' => $displayQuantity,
            'unit_cost' => $unitCost,
            'unit_price' => $unitPrice,
            'total_cost' => $quantity * $unitCost,
            'notes' => $data['notes'] ?? null,
            'occurred_at' => $occurredAt,
        ])->save();

        $this->refreshCurrentPrices($product, $batch);

        return $movement->refresh();
    }

    private function updateIntakeMovement(InventoryMovement $movement, array $data, ?int $userId): InventoryMovement
    {
        $product = Product::whereKey($movement->product_id)->lockForUpdate()->firstOrFail();
        $batch = $this->lockedBatch($movement);
        $quantity = (int) $data['quantity'];

        $warehouseDelta = $quantity - $movement->warehouse_quantity_delta;

        $this->ensureBatchLocationCanAdjust($batch, $warehouseDelta, 0);
        $this->applyProductDeltas($product, $warehouseDelta, 0);

        $unitCost = (float) $data['unit_cost'];
        $unitPrice = (float) $data['unit_price'];
        $occurredAt = $this->parseDate($data['occurred_at'] ?? null);

        $this->updateBatch($batch, $warehouseDelta, $warehouseDelta, 0, $unitCost, $unitPrice, $occurredAt, $data['notes'] ?? null);

        $movement->forceFill([
            'performed_by' => $userId ?? $movement->performed_by,
            'quantity' => $quantity,
            'warehouse_quantity_delta' => $quantity,
            'display_quantity_delta' => 0,
            'unit_cost' => $unitCost,
            'unit_price' => $unitPrice,
            'total_cost' => $quantity * $unitCost,
            'notes' => $data['notes'] ?? null,
            'occurred_at' => $occurredAt,
        ])->save();

        $this->refreshCurrentPrices($product, $batch);

        return $movement->refresh();
    }

    private function updateTransferMovement(InventoryMovement $movement, array $data, ?int $userId): InventoryMovement
    {
        $product = Product::whereKey($movement->product_id)->lockForUpdate()->firstOrFail();
        $quantity = (int) $data['quantity'];

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Enter at least one unit to move.',
            ]);
        }

        if ($quantity === (int) $movement->quantity) {
            $movement->forceFill([
                'performed_by' => $userId ?? $movement->performed_by,
                'notes' => $data['notes'] ?? null,
                'occurred_at' => $this->parseDate($data['occurred_at'] ?? null),
            ])->save();

            return $movement->refresh();
        }

        $this->reverseTransferBatches($movement);
        $this->applyProductDeltas($product, (int) $movement->quantity, -((int) $movement->quantity));

        if ($quantity > $product->warehouse_stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'The warehouse does not have enough stock to increase this transfer.',
            ]);
        }

        $allocation = $this->allocateTransferBatches($product, $quantity);
        $this->applyProductDeltas($product, -$quantity, $quantity);
        $averageUnitCost = $quantity > 0 ? $allocation['total_cost'] / $quantity : 0;

        $occurredAt = $this->parseDate($data['occurred_at'] ?? null);

        $movement->forceFill([
            'inventory_batch_id' => $allocation['single_batch_id'],
            'performed_by' => $userId ?? $movement->performed_by,
            'quantity' => $quantity,
            'warehouse_quantity_delta' => -$quantity,
            'display_quantity_delta' => $quantity,
            'unit_cost' => $averageUnitCost,
            'total_cost' => $allocation['total_cost'],
            'notes' => $data['notes'] ?? null,
            'meta' => [
                'transfer_breakdown' => $allocation['breakdown'],
            ],
            'occurred_at' => $occurredAt,
        ])->save();

        return $movement->refresh();
    }

    private function applyProductDeltas(Product $product, int $warehouseDelta, int $displayDelta): void
    {
        if ($warehouseDelta < 0 && $product->warehouse_stock_quantity < abs($warehouseDelta)) {
            throw ValidationException::withMessages([
                'quantity' => 'Warehouse stock has already moved too far to make this adjustment.',
            ]);
        }

        if ($displayDelta < 0 && $product->stock_quantity < abs($displayDelta)) {
            throw ValidationException::withMessages([
                'quantity' => 'Display stock has already moved too far to make this adjustment.',
            ]);
        }

        $product->forceFill([
            'warehouse_stock_quantity' => $product->warehouse_stock_quantity + $warehouseDelta,
            'stock_quantity' => $product->stock_quantity + $displayDelta,
        ])->save();
    }

    private function ensureBatchLocationCanAdjust(InventoryBatch $batch, int $warehouseDelta, int $displayDelta): void
    {
        if ($warehouseDelta < 0 && $batch->warehouse_remaining_quantity < abs($warehouseDelta)) {
            throw ValidationException::withMessages([
                'quantity' => 'This batch does not have enough unused warehouse stock for that correction.',
            ]);
        }

        if ($displayDelta < 0 && $batch->display_remaining_quantity < abs($displayDelta)) {
            throw ValidationException::withMessages([
                'quantity' => 'This batch does not have enough unused display stock for that correction.',
            ]);
        }
    }

    private function updateBatch(InventoryBatch $batch, int $quantityDelta, int $warehouseDelta, int $displayDelta, float $unitCost, float $unitPrice, Carbon $occurredAt, ?string $notes): void
    {
        if ($batch->quantity_received + $quantityDelta < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be at least one.',
            ]);
        }

        $batch->forceFill([
            'received_at' => $occurredAt->toDateString(),
            'quantity_received' => $batch->quantity_received + $quantityDelta,
            'remaining_quantity' => $batch->remaining_quantity + $quantityDelta,
            'warehouse_remaining_quantity' => $batch->warehouse_remaining_quantity + $warehouseDelta,
            'display_remaining_quantity' => $batch->display_remaining_quantity + $displayDelta,
            'unit_cost' => $unitCost,
            'unit_price' => $unitPrice,
            'notes' => $notes,
        ])->save();
    }

    private function lockedBatch(InventoryMovement $movement): InventoryBatch
    {
        $batch = InventoryBatch::whereKey($movement->inventory_batch_id)->lockForUpdate()->first();

        if (! $batch) {
            throw ValidationException::withMessages([
                'movement' => 'This movement is missing its stock batch and cannot be edited safely.',
            ]);
        }

        return $batch;
    }

    private function allocateTransferBatches(Product $product, int $quantity): array
    {
        $remaining = $quantity;
        $totalCost = 0;
        $breakdown = [];

        $batches = InventoryBatch::query()
            ->where('product_id', $product->id)
            ->where('warehouse_remaining_quantity', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining < 1) {
                break;
            }

            $take = min($remaining, $batch->warehouse_remaining_quantity);
            $lineCost = $take * (float) $batch->unit_cost;

            $batch->forceFill([
                'warehouse_remaining_quantity' => $batch->warehouse_remaining_quantity - $take,
                'display_remaining_quantity' => $batch->display_remaining_quantity + $take,
            ])->save();

            $breakdown[] = [
                'batch_id' => $batch->id,
                'batch_reference' => $batch->batch_reference,
                'quantity' => $take,
                'unit_cost' => (float) $batch->unit_cost,
                'cost_total' => $lineCost,
            ];

            $totalCost += $lineCost;
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'quantity' => "{$product->name} does not have enough costed warehouse stock to move to display.",
            ]);
        }

        return [
            'total_cost' => $totalCost,
            'breakdown' => $breakdown,
            'single_batch_id' => count($breakdown) === 1 ? $breakdown[0]['batch_id'] : null,
        ];
    }

    private function reverseTransferBatches(InventoryMovement $movement): void
    {
        $breakdown = data_get($movement->meta, 'transfer_breakdown', []);

        if ($breakdown === [] && $movement->inventory_batch_id) {
            $breakdown = [[
                'batch_id' => $movement->inventory_batch_id,
                'quantity' => $movement->quantity,
            ]];
        }

        if ($breakdown === []) {
            throw ValidationException::withMessages([
                'movement' => 'This transfer has no batch allocation and cannot be edited safely.',
            ]);
        }

        foreach ($breakdown as $line) {
            $quantity = (int) data_get($line, 'quantity', 0);

            if ($quantity < 1) {
                continue;
            }

            $batch = InventoryBatch::whereKey(data_get($line, 'batch_id'))->lockForUpdate()->first();

            if (! $batch || $batch->display_remaining_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'movement' => 'This transfer cannot be changed because some allocated display stock has already been sold.',
                ]);
            }

            $batch->forceFill([
                'warehouse_remaining_quantity' => $batch->warehouse_remaining_quantity + $quantity,
                'display_remaining_quantity' => $batch->display_remaining_quantity - $quantity,
            ])->save();
        }
    }

    private function consumeCostBatches(Product $product, int $quantity): array
    {
        $remaining = $quantity;
        $totalCost = 0;
        $breakdown = [];

        $batches = InventoryBatch::query()
            ->where('product_id', $product->id)
            ->where('display_remaining_quantity', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining < 1) {
                break;
            }

            $take = min($remaining, $batch->display_remaining_quantity);
            $lineCost = $take * (float) $batch->unit_cost;

            $batch->forceFill([
                'display_remaining_quantity' => $batch->display_remaining_quantity - $take,
                'remaining_quantity' => $batch->remaining_quantity - $take,
            ])->save();

            $breakdown[] = [
                'batch_reference' => $batch->batch_reference,
                'quantity' => $take,
                'unit_cost' => (float) $batch->unit_cost,
                'cost_total' => $lineCost,
            ];

            $totalCost += $lineCost;
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'payment' => "{$product->name} does not have enough costed stock. Record opening stock or stock intake first.",
            ]);
        }

        return [
            'total_cost' => $totalCost,
            'breakdown' => $breakdown,
        ];
    }

    private function createBatch(Product $product, array $data): InventoryBatch
    {
        $receivedAt = $this->parseDate($data['received_at'] ?? null);

        return InventoryBatch::create($data + [
            'product_id' => $product->id,
            'batch_reference' => $this->nextBatchReference($receivedAt),
            'received_at' => $receivedAt->toDateString(),
        ]);
    }

    private function createMovement(Product $product, array $data): InventoryMovement
    {
        return InventoryMovement::create($data + [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'reference' => $this->nextMovementReference(),
            'occurred_at' => now(),
        ]);
    }

    private function nextBatchReference(Carbon $receivedAt): string
    {
        $prefix = 'EDK-BATCH-'.$receivedAt->format('ymd');
        $latestSequence = InventoryBatch::where('batch_reference', 'like', "{$prefix}-%")
            ->pluck('batch_reference')
            ->map(function (?string $reference) use ($prefix): ?int {
                if (! $reference || ! preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $reference, $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter()
            ->max();

        return $prefix.'-'.($latestSequence ? $latestSequence + 1 : 1000);
    }

    private function nextMovementReference(): string
    {
        $prefix = 'INV-'.now()->format('ymd');
        $latestSequence = InventoryMovement::where('reference', 'like', "{$prefix}-%")
            ->pluck('reference')
            ->map(function (?string $reference) use ($prefix): ?int {
                if (! $reference || ! preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $reference, $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter()
            ->max();

        return $prefix.'-'.($latestSequence ? $latestSequence + 1 : 1000);
    }

    private function refreshCurrentPrices(Product $product, ?InventoryBatch $preferredBatch = null): void
    {
        $batch = $product->inventoryBatches()->latest('received_at')->latest('id')->first();

        if (! $batch) {
            return;
        }

        if ($preferredBatch && ! $batch->is($preferredBatch)) {
            return;
        }

        $product->forceFill([
            'cost_price' => $batch->unit_cost,
            'price' => $batch->unit_price,
        ])->save();
    }

    private function stockBalanceQuery()
    {
        return InventoryMovement::query()
            ->selectRaw('COALESCE(SUM(warehouse_quantity_delta + display_quantity_delta), 0) as balance');
    }

    private function parseDate(mixed $date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date->copy();
        }

        if ($date) {
            return Carbon::parse($date);
        }

        return now();
    }

    private function cartProducts(ShoppingList $shoppingList, bool $lockProducts = false): array
    {
        $items = collect($shoppingList->cart_items ?? [])
            ->filter(fn (array $item): bool => ! empty($item['product_id']) && (int) ($item['quantity'] ?? 0) > 0)
            ->filter(fn (array $item): bool => ($item['fulfilment_source'] ?? 'edukit') === 'edukit')
            ->values();

        if ($items->isEmpty()) {
            return [];
        }

        $query = Product::whereIn('id', $items->pluck('product_id')->all());

        if ($lockProducts) {
            $query->lockForUpdate();
        }

        $products = $query->get()->keyBy('id');

        return $items
            ->map(function (array $item) use ($products): ?array {
                $product = $products[(int) $item['product_id']] ?? null;

                if (! $product) {
                    return null;
                }

                return [
                    'item' => $item,
                    'product' => $product,
                    'quantity' => (int) $item['quantity'],
                ];
            })
            ->filter()
            ->all();
    }
}
