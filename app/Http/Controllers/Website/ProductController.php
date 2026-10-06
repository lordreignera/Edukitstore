<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);
        $selectedCategory = $search !== '' ? '' : (string) $request->query('category');
        $sort = (string) $request->query('sort', 'latest');

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($query) => $query->active()])
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->active()
            ->with('category', 'approvedSupplierOffers.supplier')
            ->when($selectedCategory !== '', function ($query) use ($selectedCategory) {
                $query->whereHas('category', fn ($category) => $category->where('slug', $selectedCategory));
            })
            ->when($search !== '', fn (Builder $query) => $this->matchSearch($query, $search))
            ->when($sort === 'price_low', fn ($query) => $query->orderByRaw($this->marketplacePriceSql().' ASC'))
            ->when($sort === 'price_high', fn ($query) => $query->orderByRaw($this->marketplacePriceSql().' DESC'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when(! in_array($sort, ['price_low', 'price_high', 'name'], true), fn ($query) => $query->latest())
            ->paginate(16)
            ->withQueryString();

        return view('website.products.index', compact('categories', 'products', 'search', 'selectedCategory', 'sort'));
    }

    public function suggest(Request $request): JsonResponse
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);

        if ($search === '') {
            return response()->json(['products' => []]);
        }

        $query = Product::query()->active();
        $this->matchSearch($query, $search);

        $products = $query
            ->orderBy('name')
            ->limit(6)
            ->get();

        return response()->json(['products' => $products->map(fn (Product $product) => [
            'name' => $product->name,
            'url' => route('website.products.show', $product),
            'price' => 'UGX '.number_format($product->marketplace_price),
        ])]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load('category', 'approvedSupplierOffers.supplier');

        return view('website.products.show', compact('product'));
    }

    private function matchSearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $inner) use ($search): void {
            $inner->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")
                ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$search}%"));
        });
    }

    private function marketplacePriceSql(): string
    {
        return "CASE WHEN products.stock_quantity > 0 THEN products.price ELSE COALESCE((SELECT MIN(supplier_offers.customer_price) FROM supplier_offers INNER JOIN suppliers ON suppliers.id = supplier_offers.supplier_id WHERE supplier_offers.product_id = products.id AND supplier_offers.status = 'approved' AND supplier_offers.direct_fulfilment = 1 AND supplier_offers.quantity_available > 0 AND suppliers.is_active = 1 AND suppliers.is_approved = 1), products.price) END";
    }
}
