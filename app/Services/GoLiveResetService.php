<?php

namespace App\Services;

use App\Models\DriverPayout;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ShoppingList;
use App\Support\DocumentStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoLiveResetService
{
    private const RESET_KEY = 'pre-launch-test-data';

    public function preview(): array
    {
        $providerCounts = ShoppingList::query()
            ->get(['payment_provider'])
            ->groupBy(fn (ShoppingList $order): string => $order->payment_provider ?: 'not_set')
            ->map(fn ($orders): int => $orders->count())
            ->all();

        return [
            'products' => Product::count(),
            'categories' => ProductCategory::count(),
            'warehouse_units' => (int) Product::sum('warehouse_stock_quantity'),
            'display_units' => (int) Product::sum('stock_quantity'),
            'priced_products' => Product::query()->where('price', '>', 0)->orWhere('cost_price', '>', 0)->count(),
            'batches' => InventoryBatch::count(),
            'movements' => InventoryMovement::count(),
            'orders' => ShoppingList::count(),
            'orders_by_provider' => $providerCounts,
            'payment_attempts' => PaymentAttempt::count(),
            'driver_payouts' => DriverPayout::count(),
            'already_completed' => DB::table('go_live_reset_runs')->where('reset_key', self::RESET_KEY)->exists(),
        ];
    }

    /**
     * Remove pre-launch operational data while retaining catalogue identity and media.
     * This operation is intentionally one-time and requires the controller's explicit
     * Super Admin, password, and confirmation checks.
     */
    public function reset(int $performedBy): array
    {
        $uploadPaths = [];

        $summary = DB::transaction(function () use ($performedBy, &$uploadPaths): array {
            $resetAlreadyExists = DB::table('go_live_reset_runs')
                ->where('reset_key', self::RESET_KEY)
                ->lockForUpdate()
                ->exists();

            if ($resetAlreadyExists) {
                throw ValidationException::withMessages([
                    'reset' => 'The one-time go-live reset has already been completed.',
                ]);
            }

            // Lock catalogue and order rows while taking the pre-reset snapshot.
            Product::query()->orderBy('id')->lockForUpdate()->get(['id']);
            $orders = ShoppingList::query()->orderBy('id')->lockForUpdate()->get(['reference', 'file_path']);
            $uploadPaths = $orders->pluck('file_path')->filter()->unique()->values()->all();
            $orderReferences = $orders->pluck('reference')->filter()->values()->all();

            $counts = [
                'products_retained' => Product::count(),
                'categories_retained' => ProductCategory::count(),
                'orders_removed' => $orders->count(),
                'payment_attempts_removed' => PaymentAttempt::count(),
                'driver_payouts_removed' => DriverPayout::count(),
                'order_notifications_removed' => $orderReferences === []
                    ? 0
                    : DB::table('notifications')->whereIn('data->reference', $orderReferences)->count(),
                'inventory_movements_removed' => InventoryMovement::count(),
                'inventory_batches_removed' => InventoryBatch::count(),
                'catalogue_prices_reset' => Product::query()->where(function ($query): void {
                    $query->where('price', '>', 0)->orWhere('cost_price', '>', 0);
                })->count(),
                'product_uploads_to_remove' => count(array_filter(
                    $uploadPaths,
                    fn ($path): bool => is_string($path) && Str::startsWith($path, 'shopping-lists/'),
                )),
            ];

            // Driver payouts restrict order deletion, so remove those child records first.
            DriverPayout::query()->delete();

            if ($orderReferences !== []) {
                DB::table('notifications')->whereIn('data->reference', $orderReferences)->delete();
            }

            // Shopping-list items and payment attempts cascade from their parent order.
            ShoppingList::query()->delete();

            // Movements reference batches and orders, so clear them before either parent.
            InventoryMovement::query()->delete();
            InventoryBatch::query()->delete();

            // Preserve each catalogue row and its descriptive/media fields. Deactivate
            // until verified prices and stock have been entered to prevent free checkout.
            Product::query()->update([
                'warehouse_stock_quantity' => 0,
                'stock_quantity' => 0,
                'cost_price' => 0,
                'price' => 0,
                'is_active' => false,
            ]);

            DB::table('go_live_reset_runs')->insert([
                'reset_key' => self::RESET_KEY,
                'performed_by' => $performedBy,
                'summary' => json_encode($counts, JSON_THROW_ON_ERROR),
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $counts;
        }, 3);

        $safeUploadPaths = array_values(array_filter(
            $uploadPaths,
            fn ($path): bool => is_string($path) && Str::startsWith($path, 'shopping-lists/'),
        ));

        if ($safeUploadPaths !== []) {
            Storage::disk(DocumentStorage::disk())->delete($safeUploadPaths);
        }

        return $summary;
    }
}
