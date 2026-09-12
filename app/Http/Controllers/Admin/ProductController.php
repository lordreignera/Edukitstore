<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $products = Product::with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($productQuery) use ($search) {
                    $productQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'search'));
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
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        Product::create($data);

        return redirect()->route('admin.products.index')->with('status', 'Product added to the master list.');
    }

    public function edit(Product $product): View
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        $data['slug'] = $product->name === $data['name'] ? $product->slug : $this->uniqueSlug($data['name'], $product);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        $product->update($data);

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product removed from the master list.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($product)],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:80'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ]);
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
