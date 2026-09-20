<?php

namespace App\Services;

use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\SupplierOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierSaleService
{
    public function ensureStock(ShoppingList $invoice): void
    {
        foreach (collect($invoice->cart_items)->where('fulfilment_source', 'supplier') as $item) {
            $offer = SupplierOffer::find($item['supplier_offer_id']);
            if (! $offer || ! $offer->isApprovedAndAvailable() || $offer->quantity_available < $item['quantity']) {
                throw ValidationException::withMessages(['cart' => ($item['name'] ?? 'A supplier product').' no longer has enough supplier stock.']);
            }
        }
    }

    public function recordPaidSale(ShoppingList $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            foreach (collect($invoice->cart_items)->where('fulfilment_source', 'supplier') as $item) {
                $offer = SupplierOffer::whereKey($item['supplier_offer_id'])->lockForUpdate()->firstOrFail();
                $existing = ShoppingListItem::where('shopping_list_id', $invoice->id)->where('supplier_offer_id', $offer->id)->first();
                if ($existing && (float) $existing->supplier_payable > 0) continue;

                $quantity = (int) $item['quantity'];
                if ($offer->quantity_available < $quantity) throw ValidationException::withMessages(['payment' => "{$offer->submitted_name} has insufficient supplier stock."]);

                $supplierPayable = $quantity * (float) $offer->supplier_price;
                $revenue = $quantity * (float) $item['unit_price'];
                $offer->decrement('quantity_available', $quantity);
                $offer->increment('quantity_sold', $quantity);

                ShoppingListItem::updateOrCreate(
                    ['shopping_list_id' => $invoice->id, 'supplier_offer_id' => $offer->id],
                    [
                        'product_id' => $offer->product_id, 'supplier_id' => $offer->supplier_id,
                        'fulfilment_source' => 'supplier', 'product_name' => $item['name'], 'sku' => $item['sku'] ?? null,
                        'quantity' => $quantity, 'unit_cost' => $offer->supplier_price, 'unit_price' => $item['unit_price'],
                        'line_total' => $revenue, 'cost_total' => $supplierPayable,
                        'supplier_payable' => $supplierPayable, 'profit_total' => $revenue - $supplierPayable,
                        'settlement_status' => 'reserved',
                    ]
                );
            }
        });
    }
}
