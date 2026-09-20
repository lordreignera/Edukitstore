<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    use HasFactory;

    public const SOURCE_OPENING_STOCK = 'opening_stock';

    public const SOURCE_STOCK_INTAKE = 'stock_intake';

    public const SOURCE_IMPORT = 'import';

    protected $fillable = [
        'product_id',
        'supplier_id',
        'created_by',
        'batch_reference',
        'source',
        'received_at',
        'quantity_received',
        'remaining_quantity',
        'warehouse_remaining_quantity',
        'display_remaining_quantity',
        'unit_cost',
        'unit_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'quantity_received' => 'integer',
            'remaining_quantity' => 'integer',
            'warehouse_remaining_quantity' => 'integer',
            'display_remaining_quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function consumedQuantity(): int
    {
        return max(0, $this->quantity_received - $this->remaining_quantity);
    }
}
