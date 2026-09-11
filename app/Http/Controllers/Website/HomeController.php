<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featuredProducts = Product::query()
            ->active()
            ->with('category')
            ->where('is_featured', true)
            ->latest()
            ->take(8)
            ->get();

        $spotlightProduct = $featuredProducts->first();

        $stageProducts = Product::query()
            ->active()
            ->with('category')
            ->when($spotlightProduct, fn ($query) => $query->whereKeyNot($spotlightProduct->id))
            ->latest()
            ->take(6)
            ->get();

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($query) => $query->active()])
            ->orderBy('name')
            ->get();

        $categoryImages = Product::query()
            ->active()
            ->whereNotNull('image_url')
            ->get(['product_category_id', 'image_url'])
            ->groupBy('product_category_id')
            ->map(fn ($products) => $products->first()->image_url);

        return view('website.home', compact('featuredProducts', 'spotlightProduct', 'stageProducts', 'categories', 'categoryImages'));
    }
}
