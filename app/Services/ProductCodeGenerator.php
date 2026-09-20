<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductCodeGenerator
{
    public function next(): string
    {
        $prefix = 'EDK'.now()->format('ym');
        $latest = Product::where('sku', 'like', "{$prefix}%")->orderByDesc('sku')->value('sku');
        $next = $latest ? ((int) substr($latest, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function uniqueSlug(string $name, ?Product $product = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $count = 2;

        while (Product::where('slug', $slug)->when($product, fn ($query) => $query->whereKeyNot($product->id))->exists()) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }
}
