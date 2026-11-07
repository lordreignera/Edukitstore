<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopping_list_id',
        'product_id',
        'supplier_offer_id',
        'supplier_id',
        'fulfilment_source',
        'product_name',
        'sku',
        'quantity',
        'ordered_quantity',
        'unavailable_quantity',
        'unavailable_reason',
        'unavailable_note',
        'unit_cost',
        'unit_price',
        'line_total',
        'cost_total',
        'profit_total',
        'supplier_payable',
        'settlement_status',
        'cost_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'ordered_quantity' => 'integer',
            'unavailable_quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'cost_total' => 'decimal:2',
            'profit_total' => 'decimal:2',
            'supplier_payable' => 'decimal:2',
            'cost_breakdown' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ShoppingListItem $item): void {
            if (! $item->ordered_quantity) {
                $item->ordered_quantity = (int) $item->quantity;
            }
        });
    }

    public static function unavailableReasons(): array
    {
        return [
            'out_of_stock' => 'Out of stock',
            'not_on_display' => 'Not on display',
            'damaged' => 'Damaged / not sellable',
            'other' => 'Other reason',
        ];
    }

    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplierOffer(): BelongsTo { return $this->belongsTo(SupplierOffer::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
}
