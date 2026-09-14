<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ShoppingList;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function recordIntake(Product $product, array $data, ?int $userId = null): InventoryMovement
    {
        return DB::transaction(function () use ($product, $data, $userId): InventoryMovement {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $quantity = (int) $data['quantity'];
            $unitCost = (float) $data['unit_cost'];
            $unitPrice = (float) ($data['unit_price'] ?? $lockedProduct->price);

            $lockedProduct->forceFill([
                'cost_price' => $unitCost,
                'price' => $unitPrice,
                'warehouse_stock_quantity' => $lockedProduct->warehouse_stock_quantity + $quantity,
            ])->save();

            return $this->createMovement($lockedProduct, [
                'performed_by' => $userId,
                'type' => InventoryMovement::TYPE_STOCK_INTAKE,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'unit_price' => $unitPrice,
                'total_cost' => $quantity * $unitCost,
                'total_revenue' => 0,
                'profit' => 0,
                'from_location' => 'supplier',
                'to_location' => 'warehouse',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function transferToDisplay(Product $product, int $quantity, ?int $userId = null, ?string $notes = null): InventoryMovement
    {
        return DB::transaction(function () use ($product, $quantity, $userId, $notes): InventoryMovement {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($lockedProduct->warehouse_stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'The warehouse does not have enough stock to move this product to the website.',
                ]);
            }

            $lockedProduct->forceFill([
                'warehouse_stock_quantity' => $lockedProduct->warehouse_stock_quantity - $quantity,
                'stock_quantity' => $lockedProduct->stock_quantity + $quantity,
            ])->save();

            return $this->createMovement($lockedProduct, [
                'performed_by' => $userId,
                'type' => InventoryMovement::TYPE_TRANSFER_TO_DISPLAY,
                'quantity' => $quantity,
                'unit_cost' => (float) $lockedProduct->cost_price,
                'unit_price' => (float) $lockedProduct->price,
                'total_cost' => $quantity * (float) $lockedProduct->cost_price,
                'total_revenue' => 0,
                'profit' => 0,
                'from_location' => 'warehouse',
                'to_location' => 'website_display',
                'notes' => $notes,
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
                $unitCost = (float) ($item['unit_cost'] ?? $product->cost_price);

                $product->forceFill([
                    'stock_quantity' => $product->stock_quantity - $quantity,
                ])->save();

                $this->createMovement($product, [
                    'shopping_list_id' => $lockedInvoice->id,
                    'performed_by' => $userId,
                    'type' => InventoryMovement::TYPE_SALE_PAID,
                    'reference' => $lockedInvoice->reference,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'unit_price' => $unitPrice,
                    'total_cost' => $quantity * $unitCost,
                    'total_revenue' => $quantity * $unitPrice,
                    'profit' => $quantity * ($unitPrice - $unitCost),
                    'from_location' => 'website_display',
                    'to_location' => 'customer_order',
                    'notes' => 'Stock reserved after verified payment.',
                    'meta' => [
                        'customer' => $lockedInvoice->parent_name,
                        'phone' => $lockedInvoice->phone,
                        'school' => $lockedInvoice->school_name,
                    ],
                ]);
            }
        });
    }

    private function createMovement(Product $product, array $data): InventoryMovement
    {
        return InventoryMovement::create($data + [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
        ]);
    }

    private function cartProducts(ShoppingList $shoppingList, bool $lockProducts = false): array
    {
        $items = collect($shoppingList->cart_items ?? [])
            ->filter(fn (array $item): bool => ! empty($item['product_id']) && (int) ($item['quantity'] ?? 0) > 0)
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
