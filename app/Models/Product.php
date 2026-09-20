<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_category_id',
        'name',
        'slug',
        'sku',
        'description',
        'brand',
        'unit',
        'cost_price',
        'price',
        'warehouse_stock_quantity',
        'stock_quantity',
        'reorder_level',
        'image_path',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'price' => 'decimal:2',
            'warehouse_stock_quantity' => 'integer',
            'stock_quantity' => 'integer',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function shoppingListItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class);
    }

    public function supplierOffers(): HasMany
    {
        return $this->hasMany(SupplierOffer::class);
    }

    public function approvedSupplierOffers(): HasMany
    {
        return $this->supplierOffers()
            ->where('status', SupplierOffer::STATUS_APPROVED)
            ->where('direct_fulfilment', true)
            ->where('quantity_available', '>', 0)
            ->whereHas('supplier', fn ($query) => $query->where('is_approved', true)->where('is_active', true));
    }

    protected function marketplaceStockQuantity(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->stock_quantity
            + (int) ($this->relationLoaded('approvedSupplierOffers')
                ? $this->approvedSupplierOffers->sum('quantity_available')
                : $this->approvedSupplierOffers()->sum('quantity_available')));
    }

    protected function marketplacePrice(): Attribute
    {
        return Attribute::get(function (): float {
            if ($this->stock_quantity > 0) return (float) $this->price;
            $offers = $this->relationLoaded('approvedSupplierOffers')
                ? $this->approvedSupplierOffers
                : $this->approvedSupplierOffers()->get();
            return (float) ($offers->pluck('customer_price')->filter()->min() ?? $this->price);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->image_path) {
                return null;
            }

            $path = ltrim($this->image_path, '/');

            if (preg_match('/^https?:\/\//i', $path)) {
                return $path;
            }

            if (is_file(public_path($path))) {
                return '/'.$path;
            }

            if (str_starts_with($path, 'products/seed/')) {
                $publicSeedPath = 'images/products/'.basename($path);

                if (is_file(public_path($publicSeedPath))) {
                    return '/'.$publicSeedPath;
                }
            }

            if (str_starts_with($path, 'images/products/')) {
                $storageSeedPath = 'products/seed/'.basename($path);

                if (Storage::disk(self::imageDisk())->exists($storageSeedPath)) {
                    return Storage::disk(self::imageDisk())->url($storageSeedPath);
                }
            }

            return Storage::disk(self::imageDisk())->url($path);
        });
    }

    public static function imageDisk(): string
    {
        return config('filesystems.product_images_disk', 'public');
    }

    protected function profitPerUnit(): Attribute
    {
        return Attribute::get(fn (): float => max(0, (float) $this->price - (float) $this->cost_price));
    }

    protected function grossMarginPercentage(): Attribute
    {
        return Attribute::get(function (): float {
            if ((float) $this->price <= 0) {
                return 0;
            }

            return round(($this->profit_per_unit / (float) $this->price) * 100, 1);
        });
    }

    protected function totalStockQuantity(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->warehouse_stock_quantity + (int) $this->stock_quantity);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowDisplayStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'reorder_level');
    }
}
