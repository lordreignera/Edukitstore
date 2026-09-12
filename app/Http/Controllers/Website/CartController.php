<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.$product->stock_quantity],
        ]);
        $quantity = (int) ($data['quantity'] ?? 1);

        $cart = session('cart', []);
        $cart[$product->id] = min($product->stock_quantity, ($cart[$product->id] ?? 0) + $quantity);

        session(['cart' => $cart]);

        return back()->with('status', "{$product->name} added to cart.");
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$product->stock_quantity],
        ]);

        $cart = session('cart', []);
        $cart[$product->id] = (int) $data['quantity'];

        session(['cart' => $cart]);

        return back()->with('status', "{$product->name} quantity updated.");
    }

    public function submit(Request $request): RedirectResponse
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return back()->withErrors(['cart' => 'Add at least one product before submitting your cart.']);
        }

        $data = $request->validate([
            'parent_name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'school_name' => ['nullable', 'string', 'max:160'],
            'learner_name' => ['nullable', 'string', 'max:160'],
            'class_level' => ['nullable', 'string', 'max:80'],
            'delivery_location' => ['nullable', 'string', 'max:255'],
            'delivery_preference' => ['required', 'in:school,home,pickup'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $products = Product::query()
            ->active()
            ->whereIn('id', array_keys($cart))
            ->get();

        $items = $products->map(function (Product $product) use ($cart): array {
            $quantity = (int) ($cart[$product->id] ?? 0);
            $unitPrice = (int) $product->price;

            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $unitPrice * $quantity,
            ];
        })->filter(fn (array $item): bool => $item['quantity'] > 0)->values();

        if ($items->isEmpty()) {
            return back()->withErrors(['cart' => 'The products in your cart are no longer available.']);
        }

        $shoppingList = ShoppingList::create($data + [
            'source' => ShoppingList::SOURCE_CART,
            'reference' => $this->uniqueReference(),
            'cart_items' => $items->all(),
            'items_subtotal' => $items->sum('line_total'),
            'status' => ShoppingList::STATUS_PENDING,
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);

        session()->forget('cart');

        return redirect()
            ->route('website.quote.show', $shoppingList->reference)
            ->with('status', 'Your cart has been submitted. EduKit will add the delivery fee and prepare your invoice.');
    }

    public function quote(string $reference): View
    {
        $shoppingList = ShoppingList::with('assignedDriver')->where('reference', $reference)->firstOrFail();

        return view('website.quote', compact('shoppingList'));
    }

    public function pay(Request $request, string $reference): RedirectResponse
    {
        $shoppingList = ShoppingList::where('reference', $reference)->firstOrFail();

        abort_unless($shoppingList->status === ShoppingList::STATUS_QUOTED && $shoppingList->estimated_total, 404);

        $secretKey = config('services.flutterwave.secret_key');

        if (! $secretKey) {
            return back()->withErrors(['payment' => 'Flutterwave checkout is not connected yet. Add the Flutterwave secret key, then this invoice can be paid online.']);
        }

        $txRef = $shoppingList->reference.'-'.Str::upper(Str::random(6));
        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $txRef,
                'amount' => $shoppingList->estimated_total,
                'currency' => 'UGX',
                'redirect_url' => route('website.payments.flutterwave.callback'),
                'customer' => [
                    'email' => $this->customerEmail($shoppingList),
                    'name' => $shoppingList->parent_name,
                    'phonenumber' => $shoppingList->phone,
                ],
                'customizations' => [
                    'title' => 'EduKit Invoice '.$shoppingList->reference,
                    'description' => 'School supplies invoice including delivery/convenience fee.',
                ],
                'meta' => [
                    'shopping_list_id' => $shoppingList->id,
                    'reference' => $shoppingList->reference,
                ],
            ]);

        $checkoutUrl = $response->json('data.link');

        if (! $response->successful() || ! $checkoutUrl) {
            return back()->withErrors(['payment' => 'Flutterwave could not start checkout. Please try again or contact EduKit support.']);
        }

        $shoppingList->update([
            'payment_status' => ShoppingList::PAYMENT_PENDING,
            'payment_provider' => 'flutterwave',
            'payment_reference' => $txRef,
        ]);

        return redirect()->away($checkoutUrl);
    }

    public function flutterwaveCallback(Request $request): RedirectResponse
    {
        $txRef = (string) $request->query('tx_ref');
        $transactionId = (string) $request->query('transaction_id');

        abort_if($txRef === '', 404);

        $shoppingList = ShoppingList::where('payment_reference', $txRef)->firstOrFail();

        if ($request->query('status') !== 'successful' || $transactionId === '') {
            return redirect()
                ->route('website.quote.show', $shoppingList->reference)
                ->withErrors(['payment' => 'The payment was not completed. You can try again from this invoice.']);
        }

        $secretKey = config('services.flutterwave.secret_key');

        if (! $secretKey) {
            return redirect()
                ->route('website.quote.show', $shoppingList->reference)
                ->withErrors(['payment' => 'Flutterwave verification is not connected yet.']);
        }

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

        $data = $response->json('data', []);
        $amount = (float) ($data['amount'] ?? 0);

        $verified = $response->successful()
            && $response->json('status') === 'success'
            && ($data['status'] ?? null) === 'successful'
            && ($data['tx_ref'] ?? null) === $txRef
            && strtoupper((string) ($data['currency'] ?? '')) === 'UGX'
            && $amount >= (float) $shoppingList->estimated_total;

        if (! $verified) {
            return redirect()
                ->route('website.quote.show', $shoppingList->reference)
                ->withErrors(['payment' => 'Flutterwave payment verification failed. Please contact EduKit support if money was deducted.']);
        }

        $shoppingList->update([
            'payment_status' => ShoppingList::PAYMENT_PAID,
            'payment_provider' => 'flutterwave',
            'paid_at' => now(),
        ]);

        return redirect()
            ->route('website.quote.show', $shoppingList->reference)
            ->with('status', 'Payment received. EduKit can now begin fulfilment.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);

        session(['cart' => $cart]);

        return back()->with('status', "{$product->name} removed from cart.");
    }

    private function uniqueReference(): string
    {
        do {
            $reference = 'EDK-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (ShoppingList::where('reference', $reference)->exists());

        return $reference;
    }

    private function customerEmail(ShoppingList $shoppingList): string
    {
        if ($shoppingList->email && filter_var($shoppingList->email, FILTER_VALIDATE_EMAIL)) {
            return $shoppingList->email;
        }

        return 'invoice-'.$shoppingList->id.'@edukit.local';
    }
}
