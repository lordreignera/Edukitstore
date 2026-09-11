<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = session('cart', []);
        $productIds = array_keys($cart);

        $products = Product::query()
            ->with('category')
            ->whereIn('id', $productIds)
            ->get()
            ->map(function (Product $product) use ($cart) {
                $product->cart_quantity = $cart[$product->id] ?? 0;
                $product->cart_line_total = $product->price * $product->cart_quantity;

                return $product;
            });

        $subtotal = $products->sum('cart_line_total');

        return view('website.cart.index', compact('products', 'subtotal'));
    }

    public function store(Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $cart = session('cart', []);
        $cart[$product->id] = ($cart[$product->id] ?? 0) + 1;

        session(['cart' => $cart]);

        return back()->with('status', "{$product->name} added to cart.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);

        session(['cart' => $cart]);

        return back()->with('status', "{$product->name} removed from cart.");
    }
}
