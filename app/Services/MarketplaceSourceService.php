<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SupplierOffer;
use Illuminate\Validation\ValidationException;

class MarketplaceSourceService
{
    public function source(Product $product, ?int $offerId = null): array
    {
        if ($offerId) {
            $offer = SupplierOffer::with('supplier')->whereKey($offerId)
                ->where('product_id', $product->id)
                ->where('status', SupplierOffer::STATUS_APPROVED)
                ->where('direct_fulfilment', true)
                ->where('quantity_available', '>', 0)
                ->whereHas('supplier', fn ($query) => $query->where('is_approved', true)->where('is_active', true))
                ->first();

            if (! $offer) throw ValidationException::withMessages(['cart' => 'That supplier stock is no longer available.']);
            return $this->supplierSource($offer);
        }

        if ($product->stock_quantity > 0) {
            return ['type' => 'edukit', 'offer' => null, 'quantity' => (int) $product->stock_quantity, 'price' => (float) $product->price, 'label' => 'Fulfilled by EduKit'];
        }

        $offer = $product->approvedSupplierOffers()->with('supplier')->orderBy('customer_price')->first();
        if (! $offer) throw ValidationException::withMessages(['cart' => "{$product->name} is currently out of stock."]);
        return $this->supplierSource($offer);
    }

    public function supplierSource(SupplierOffer $offer): array
    {
        return [
            'type' => 'supplier', 'offer' => $offer, 'quantity' => (int) $offer->quantity_available,
            'price' => (float) $offer->customer_price, 'label' => 'Dispatched by '.$offer->supplier->business_name,
        ];
    }
}
