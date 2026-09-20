<?php

namespace Database\Seeders;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OpeningStockSeeder extends Seeder
{
    public function run(): void
    {
        $products = collect(json_decode(
            file_get_contents(database_path('seeders/data/edukit_products.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        ));
        $inventory = app(InventoryService::class);
        $openingDate = Carbon::now()->startOfMonth()->toDateString();

        foreach ($products as $item) {
            $product = Product::where('slug', $item['slug'] ?? Str::slug($item['name']))->first();

            if (! $product || $product->inventoryBatches()->where('source', InventoryBatch::SOURCE_OPENING_STOCK)->exists()) {
                continue;
            }

            $displayStock = (int) ($item['stock_quantity'] ?? 0);
            $warehouseStock = (int) ($item['warehouse_stock_quantity'] ?? $this->estimatedWarehouseStock($displayStock));

            if ($displayStock + $warehouseStock < 1) {
                continue;
            }

            $inventory->recordOpeningStock($product, [
                'warehouse_quantity' => $warehouseStock,
                'display_quantity' => $displayStock,
                'unit_cost' => $product->cost_price,
                'unit_price' => $item['price'],
                'occurred_at' => $openingDate,
                'notes' => 'Seeded opening balance from EduKit product list.',
            ]);
        }
    }

    private function estimatedWarehouseStock(int $displayStock): int
    {
        return (int) max(10, round($displayStock * 0.6));
    }
}
