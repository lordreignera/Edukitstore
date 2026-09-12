<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $selectedCategory = $search !== '' ? '' : (string) $request->query('category');

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->active()
            ->with('category')
            ->when($selectedCategory !== '', function ($query) use ($selectedCategory) {
                $query->whereHas('category', fn ($category) => $category->where('slug', $selectedCategory));
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('website.products.index', compact('categories', 'products', 'search', 'selectedCategory'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load('category');

        return view('website.products.show', compact('product'));
    }
}
