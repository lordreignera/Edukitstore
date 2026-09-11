<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
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
            Product::firstOrCreate(
                ['sku' => $product['sku']],
                [
                    'product_category_id' => $categories[$product['category']],
                    'name' => $product['name'],
                    'slug' => $product['slug'] ?? Str::slug($product['name']),
                    'description' => $product['description'],
                    'brand' => $product['brand'],
                    'unit' => $product['unit'],
                    'price' => $product['price'],
                    'stock_quantity' => $product['stock_quantity'],
                    'image_url' => $product['image_url'],
                    'is_active' => true,
                    'is_featured' => $product['is_featured'],
                ]
            );
        }
    }
}
