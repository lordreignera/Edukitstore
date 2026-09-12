<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'productForm' => new Product(['is_active' => true]),
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
            'product' => new Product(['is_active' => true]),
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        unset($data['image']);

        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['sku'] = $this->nextProductCode();
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['image_path'] = $this->storeImage($request);

        Product::create($data);

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

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        unset($data['image']);

        $data['slug'] = $product->name === $data['name'] ? $product->slug : $this->uniqueSlug($data['name'], $product);
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
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'image' => [$product ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
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

    private function nextProductCode(): string
    {
        $prefix = 'EDK'.now()->format('ym');
        $latest = Product::where('sku', 'like', "{$prefix}%")->orderByDesc('sku')->value('sku');
        $next = $latest ? ((int) substr($latest, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function uniqueSlug(string $name, ?Product $product = null): string
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
