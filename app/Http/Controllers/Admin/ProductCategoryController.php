<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');

        $categories = ProductCategory::withCount('products')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($categoryQuery) use ($search) {
                    $categoryQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'hidden', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.product-categories.index', [
            'categories' => $categories,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.product-categories.create', [
            'category' => new ProductCategory(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        ProductCategory::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.product-categories.index')->with('status', 'Category created.');
    }

    public function show(ProductCategory $productCategory): View
    {
        return view('admin.product-categories.show', [
            'category' => $productCategory->loadCount('products')->load(['products' => fn ($query) => $query->latest()->limit(10)]),
        ]);
    }

    public function edit(ProductCategory $productCategory): View
    {
        return view('admin.product-categories.edit', [
            'category' => $productCategory,
        ]);
    }

    public function update(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        $data = $this->validatedData($request, $productCategory);
        $data['slug'] = $productCategory->name === $data['name']
            ? $productCategory->slug
            : $this->uniqueSlug($data['name'], $productCategory);
        $data['is_active'] = $request->boolean('is_active');

        $productCategory->update($data);

        return redirect()->route('admin.product-categories.index')->with('status', 'Category updated.');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $productCategory->delete();

        return redirect()->route('admin.product-categories.index')->with('status', 'Category deleted. Existing products were moved to Uncategorised.');
    }

    private function validatedData(Request $request, ?ProductCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('product_categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $name, ?ProductCategory $category = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $count = 2;

        while (ProductCategory::where('slug', $slug)->when($category, fn ($query) => $query->whereKeyNot($category->id))->exists()) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }
}
