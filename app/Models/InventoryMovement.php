<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    public const TYPE_OPENING_STOCK = 'opening_stock';

    public const TYPE_STOCK_INTAKE = 'stock_intake';

    public const TYPE_TRANSFER_TO_DISPLAY = 'transfer_to_display';

    public const TYPE_SALE_PAID = 'sale_paid';

    protected $fillable = [
        'product_id',
        'inventory_batch_id',
        'shopping_list_id',
        'performed_by',
        'type',
        'reference',
        'product_name',
        'sku',
        'quantity',
        'warehouse_quantity_delta',
        'display_quantity_delta',
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
            'warehouse_quantity_delta' => 'integer',
            'display_quantity_delta' => 'integer',
            'unit_cost' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'total_revenue' => 'decimal:2',
            'profit' => 'decimal:2',
            'meta' => 'array',
            'occurred_at' => 'datetime',
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

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public static function labels(): array
    {
        return [
            self::TYPE_OPENING_STOCK => 'Opening stock',
            self::TYPE_STOCK_INTAKE => 'Stock intake',
            self::TYPE_TRANSFER_TO_DISPLAY => 'Moved to website/display',
            self::TYPE_SALE_PAID => 'Paid sale',
        ];
    }

    public function isEditable(): bool
    {
        return in_array($this->type, [
            self::TYPE_OPENING_STOCK,
            self::TYPE_STOCK_INTAKE,
            self::TYPE_TRANSFER_TO_DISPLAY,
        ], true);
    }
}
