<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'price',
        'stock_quantity',
        'image_path',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
