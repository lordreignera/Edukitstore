<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductCodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EduKitProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = collect(json_decode(
            file_get_contents(database_path('seeders/data/edukit_products.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        ));
        $codes = app(ProductCodeGenerator::class);

        $categories = collect([
            'School Uniforms',
            'Books',
            'Stationery',
            'Shoes',
            'Bags',
            'Bedding and Linen',
            'Toiletries',
            'School Equipment',
            'Other Supplies',
        ])
            ->merge($products->pluck('category'))
            ->unique()
            ->values()
            ->mapWithKeys(function (string $name): array {
                $category = ProductCategory::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'is_active' => true]
                );

                return [$name => $category->id];
            });

        foreach ($products as $product) {
            $imagePath = $this->storeSeedImage($product['image_url']);
            $slug = $product['slug'] ?? Str::slug($product['name']);

            $existingProduct = Product::where('slug', $slug)->first();

            if ($existingProduct) {
                if ($this->shouldRefreshSeedImage($existingProduct->image_path) && $imagePath) {
                    $existingProduct->update(['image_path' => $imagePath]);
                }

                continue;
            }

            Product::create([
                'slug' => $slug,
                'product_category_id' => $categories[$product['category']],
                'name' => $product['name'],
                'sku' => $codes->next(),
                'description' => $product['description'],
                'brand' => $product['brand'],
                'unit' => $product['unit'],
                'cost_price' => $product['cost_price'] ?? $this->estimatedCostPrice((int) $product['price']),
                'price' => $product['price'],
                'warehouse_stock_quantity' => 0,
                'stock_quantity' => 0,
                'reorder_level' => $product['reorder_level'] ?? $this->estimatedReorderLevel((int) $product['stock_quantity']),
                'image_path' => $imagePath,
                'is_active' => true,
                'is_featured' => $product['is_featured'],
            ]);
        }
    }

    private function storeSeedImage(string $sourceUrl): ?string
    {
        $sourcePath = public_path(ltrim($sourceUrl, '/'));

        if (! is_file($sourcePath)) {
            return null;
        }

        $path = 'products/seed/'.basename($sourcePath);

        if (! Storage::disk(Product::imageDisk())->exists($path)) {
            Storage::disk(Product::imageDisk())->put($path, file_get_contents($sourcePath));
        }

        return $path;
    }

    private function shouldRefreshSeedImage(?string $imagePath): bool
    {
        if (! $imagePath) {
            return true;
        }

        $path = ltrim($imagePath, '/');

        return str_starts_with($path, 'images/products/')
            || str_starts_with($path, 'products/seed/');
    }

    private function estimatedCostPrice(int $price): int
    {
        return (int) max(0, round(($price * 0.8) / 100) * 100);
    }

    private function estimatedReorderLevel(int $displayStock): int
    {
        return (int) max(5, round($displayStock * 0.15));
    }
}
