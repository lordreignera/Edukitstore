<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    public const TYPE_STOCK_INTAKE = 'stock_intake';

    public const TYPE_TRANSFER_TO_DISPLAY = 'transfer_to_display';

    public const TYPE_SALE_PAID = 'sale_paid';

    protected $fillable = [
        'product_id',
        'shopping_list_id',
        'performed_by',
        'type',
        'reference',
        'product_name',
        'sku',
        'quantity',
        'unit_cost',
        'unit_price',
        'total_cost',
        'total_revenue',
        'profit',
        'from_location',
        'to_location',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'total_revenue' => 'decimal:2',
            'profit' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public static function labels(): array
    {
        return [
            self::TYPE_STOCK_INTAKE => 'Stock intake',
            self::TYPE_TRANSFER_TO_DISPLAY => 'Moved to website/display',
            self::TYPE_SALE_PAID => 'Paid sale',
        ];
    }
}
