<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class InventoryImportService
{
    public const HEADERS = [
        'source',
        'stock_date',
        'sku',
        'name',
        'category',
        'description',
        'brand',
        'unit',
        'unit_cost',
        'customer_price',
        'warehouse_quantity',
        'display_quantity',
        'reorder_level',
        'is_active',
        'is_featured',
        'notes',
    ];

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ProductCodeGenerator $codes,
    ) {}

    public function import(UploadedFile $file, ?int $userId = null): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            return ['imported' => 0, 'errors' => ['Could not open the CSV file.']];
        }

        $headers = $this->normalizeHeaders(fgetcsv($handle) ?: []);
        $missing = array_diff(self::HEADERS, $headers);

        if ($missing !== []) {
            fclose($handle);

            return [
                'imported' => 0,
                'errors' => ['Missing CSV columns: '.implode(', ', $missing).'. Download the template and try again.'],
            ];
        }

        $imported = 0;
        $errors = [];
        $lineNumber = 1;

        while (($line = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if ($this->emptyLine($line)) {
                continue;
            }

            $row = $this->rowFromLine($headers, $line);
            $row = $this->normalizeRow($row);

            $validator = Validator::make($row, [
                'source' => ['nullable', Rule::in([InventoryBatch::SOURCE_OPENING_STOCK, InventoryBatch::SOURCE_STOCK_INTAKE])],
                'stock_date' => ['nullable', 'date'],
                'sku' => ['nullable', 'string', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'category' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'brand' => ['nullable', 'string', 'max:120'],
                'unit' => ['nullable', 'string', 'max:80'],
                'unit_cost' => ['required', 'numeric', 'min:0'],
                'customer_price' => ['required', 'numeric', 'min:0'],
                'warehouse_quantity' => ['nullable', 'integer', 'min:0'],
                'display_quantity' => ['nullable', 'integer', 'min:0'],
                'reorder_level' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
                'is_featured' => ['nullable', 'boolean'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Line {$lineNumber}: ".$validator->errors()->first();
                continue;
            }

            try {
                DB::transaction(fn () => $this->importRow($row, $userId));
                $imported++;
            } catch (Throwable $exception) {
                $errors[] = "Line {$lineNumber}: ".$exception->getMessage();
            }
        }

        fclose($handle);

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    public function templateRows(): array
    {
        return [
            self::HEADERS,
            [
                InventoryBatch::SOURCE_OPENING_STOCK,
                now()->toDateString(),
                '',
                'Exercise Books Dozen',
                'Books',
                'Ruled exercise books bought before EduKit launch.',
                'Picfare',
                'Dozen',
                '1600',
                '1800',
                '100',
                '20',
                '10',
                '1',
                '1',
                'Opening stock at launch',
            ],
        ];
    }

    private function importRow(array $row, ?int $userId): void
    {
        $source = $row['source'] ?: InventoryBatch::SOURCE_OPENING_STOCK;
        $warehouseQuantity = (int) ($row['warehouse_quantity'] ?? 0);
        $displayQuantity = (int) ($row['display_quantity'] ?? 0);
        $quantity = $warehouseQuantity + $displayQuantity;
        $product = $this->findOrCreateProduct($row);

        if ($quantity < 1) {
            return;
        }

        if ($source === InventoryBatch::SOURCE_OPENING_STOCK) {
            $this->inventory->recordOpeningStock($product, [
                'warehouse_quantity' => $warehouseQuantity,
                'display_quantity' => $displayQuantity,
                'unit_cost' => (float) $row['unit_cost'],
                'unit_price' => (float) $row['customer_price'],
                'occurred_at' => $row['stock_date'] ?: now()->toDateString(),
                'notes' => $row['notes'],
            ], $userId);

            return;
        }

        $this->inventory->recordIntake($product, [
            'quantity' => $quantity,
            'unit_cost' => (float) $row['unit_cost'],
            'unit_price' => (float) $row['customer_price'],
            'occurred_at' => $row['stock_date'] ?: now()->toDateString(),
            'notes' => $row['notes'],
        ], $userId);

        if ($displayQuantity > 0) {
            $this->inventory->transferToDisplay($product, $displayQuantity, $userId, 'Auto display transfer from CSV import.', $row['stock_date'] ?: now()->toDateString());
        }
    }

    private function findOrCreateProduct(array $row): Product
    {
        $sku = trim((string) ($row['sku'] ?? ''));
        $product = $sku !== '' ? Product::where('sku', $sku)->first() : null;
        $product ??= Product::where('slug', Str::slug($row['name']))->first();

        $categoryId = $this->categoryId($row['category']);
        $attributes = [
            'product_category_id' => $categoryId,
            'name' => $row['name'],
            'description' => $row['description'],
            'brand' => $row['brand'],
            'unit' => $row['unit'],
            'cost_price' => (float) $row['unit_cost'],
            'price' => (float) $row['customer_price'],
            'reorder_level' => (int) ($row['reorder_level'] ?? 0),
            'is_active' => $row['is_active'] ?? true,
            'is_featured' => $row['is_featured'] ?? false,
        ];

        if ($product) {
            $product->update($attributes);

            return $product;
        }

        return Product::create($attributes + [
            'slug' => $this->codes->uniqueSlug($row['name']),
            'sku' => $this->codes->next(),
            'warehouse_stock_quantity' => 0,
            'stock_quantity' => 0,
        ]);
    }

    private function categoryId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return ProductCategory::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'is_active' => true]
        )->id;
    }

    private function normalizeHeaders(array $headers): array
    {
        return collect($headers)
            ->map(fn ($header): string => $this->normalizeKey((string) $header))
            ->all();
    }

    private function rowFromLine(array $headers, array $line): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            $row[$header] = $line[$index] ?? null;
        }

        return $row;
    }

    private function normalizeRow(array $row): array
    {
        $numericFields = ['unit_cost', 'customer_price', 'warehouse_quantity', 'display_quantity', 'reorder_level'];
        $booleanFields = ['is_active', 'is_featured'];

        foreach ($row as $key => $value) {
            $row[$key] = is_string($value) ? trim($value) : $value;
        }

        foreach ($numericFields as $field) {
            $row[$field] = $this->numericValue($row[$field] ?? 0);
        }

        foreach ($booleanFields as $field) {
            $row[$field] = $this->booleanValue($row[$field] ?? null);
        }

        $row['source'] = $row['source'] ?: InventoryBatch::SOURCE_OPENING_STOCK;

        return $row;
    }

    private function normalizeKey(string $key): string
    {
        $key = preg_replace('/^\xEF\xBB\xBF/', '', $key) ?: $key;

        return Str::of($key)
            ->lower()
            ->replace([' ', '-'], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();
    }

    private function numericValue(mixed $value): int|float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        $number = preg_replace('/[^0-9.]/', '', (string) $value);

        return $number === '' ? 0 : $number + 0;
    }

    private function booleanValue(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function emptyLine(array $line): bool
    {
        return collect($line)->every(fn ($value): bool => trim((string) $value) === '');
    }
}
