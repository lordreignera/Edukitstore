<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\InventoryService;
use App\Services\ProductCodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $categoryId = (string) $request->query('category');
        $status = (string) $request->query('status');
        $featured = (string) $request->query('featured');

        $products = Product::with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($productQuery) use ($search) {
                    $productQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when($categoryId !== '', fn ($query) => $query->where('product_category_id', $categoryId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'hidden', fn ($query) => $query->where('is_active', false))
            ->when($featured === 'yes', fn ($query) => $query->where('is_featured', true))
            ->when($featured === 'no', fn ($query) => $query->where('is_featured', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = ProductCategory::orderBy('name')->get();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'categoryId' => $categoryId,
            'status' => $status,
            'featured' => $featured,
        ]);
    }

    public function create(): View
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.create', [
            'product' => new Product(['is_active' => true, 'cost_price' => 0, 'price' => 0, 'reorder_level' => 0]),
            'categories' => $categories,
        ]);
    }

    public function store(Request $request, InventoryService $inventory, ProductCodeGenerator $codes): RedirectResponse
    {
        $data = $this->validatedData($request);
        $openingStock = $this->openingStockData($data);
        unset($data['image']);
        unset($data['opening_stock_date'], $data['opening_warehouse_quantity'], $data['opening_display_quantity']);

        $data['slug'] = $codes->uniqueSlug($data['name']);
        $data['sku'] = $codes->next();
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['image_path'] = $this->storeImage($request);
        $data['warehouse_stock_quantity'] = 0;
        $data['stock_quantity'] = 0;

        DB::transaction(function () use ($data, $openingStock, $inventory): void {
            $product = Product::create($data);

            if ($openingStock['warehouse_quantity'] + $openingStock['display_quantity'] > 0) {
                $inventory->recordOpeningStock($product, [
                    'warehouse_quantity' => $openingStock['warehouse_quantity'],
                    'display_quantity' => $openingStock['display_quantity'],
                    'unit_cost' => $data['cost_price'],
                    'unit_price' => $data['price'],
                    'occurred_at' => $openingStock['occurred_at'],
                    'notes' => 'Opening stock recorded during product setup.',
                ], auth()->id());
            }
        });

        return redirect()->route('admin.products.index')->with('status', 'Product added to the master list.');
    }

    public function show(Product $product): View
    {
        return view('admin.products.show', [
            'product' => $product->load('category'),
        ]);
    }

    public function edit(Product $product): View
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product, ProductCodeGenerator $codes): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        unset($data['image']);
        unset($data['opening_stock_date'], $data['opening_warehouse_quantity'], $data['opening_display_quantity']);

        $data['slug'] = $product->name === $data['name'] ? $product->slug : $codes->uniqueSlug($data['name'], $product);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('image')) {
            $this->deleteStoredImage($product);
            $data['image_path'] = $this->storeImage($request);
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->deleteStoredImage($product);
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product removed from the master list.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:80'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'opening_stock_date' => ['nullable', 'date'],
            'opening_warehouse_quantity' => ['nullable', 'integer', 'min:0'],
            'opening_display_quantity' => ['nullable', 'integer', 'min:0'],
            'image' => [$product ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }

    private function openingStockData(array $data): array
    {
        return [
            'occurred_at' => $data['opening_stock_date'] ?? now()->toDateString(),
            'warehouse_quantity' => (int) ($data['opening_warehouse_quantity'] ?? 0),
            'display_quantity' => (int) ($data['opening_display_quantity'] ?? 0),
        ];
    }

    private function storeImage(Request $request): ?string
    {
        return $request->file('image')?->store('products', Product::imageDisk());
    }

    private function deleteStoredImage(Product $product): void
    {
        if ($product->image_path && Storage::disk(Product::imageDisk())->exists($product->image_path)) {
            Storage::disk(Product::imageDisk())->delete($product->image_path);
        }
    }

}
