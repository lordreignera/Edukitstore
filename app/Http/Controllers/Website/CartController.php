<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Services\FlutterwavePaymentService;
use App\Services\DemoPaymentService;
use App\Services\InventoryService;
use App\Services\MarketplaceSourceService;
use App\Services\SchoolDeliveryService;
use App\Services\SupplierSaleService;
use App\Support\InvoiceAccess;
use App\Support\PaymentMode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(SchoolDeliveryService $delivery, MarketplaceSourceService $sources): View
    {
        $cart = session('cart', []);
        $productIds = array_keys($cart);

        $products = Product::query()
            ->active()
            ->with('category', 'approvedSupplierOffers.supplier')
            ->whereIn('id', $productIds)
            ->get()
            ->map(function (Product $product) use ($cart, $sources): ?Product {
                $offerId = session('cart_sources.'.$product->id);
                try {
                    $source = $sources->source($product, $offerId ? (int) $offerId : null);
                } catch (ValidationException) {
                    return null;
                }
                $product->cart_quantity = min((int) ($cart[$product->id] ?? 0), $source['quantity']);
                $product->cart_source = $source;
                $product->cart_unit_price = $source['price'];
                $product->cart_line_total = $source['price'] * $product->cart_quantity;

                return $product;
            })->filter(fn (?Product $product): bool => $product !== null && $product->cart_quantity > 0)->values();

        $availableCart = $products->mapWithKeys(fn (Product $product) => [$product->id => $product->cart_quantity])->all();
        $cartNotice = null;
        if ($availableCart != $cart) {
            $cartNotice = 'Some cart items or quantities changed because stock is no longer available. Review your cart before checkout.';
            session(['cart' => $availableCart]);
            foreach (array_diff(array_keys($cart), array_keys($availableCart)) as $removedId) {
                session()->forget('cart_sources.'.$removedId);
            }
        }

        $subtotal = $products->sum('cart_line_total');
        $schools = $delivery->activeSchools();
        $supplierFeeProfiles = $products->filter(fn ($product) => $product->cart_source['type'] === 'supplier')
            ->map(fn ($product) => $product->cart_source['offer']->supplier)
            ->unique('id')->values()->map(fn ($supplier) => [
                'district' => $supplier->district,
                'local_fee' => $supplier->local_delivery_fee,
                'other_fee' => $supplier->other_district_delivery_fee,
            ]);
        $hasEdukitItems = $products->contains(fn ($product) => $product->cart_source['type'] === 'edukit');

        return view('website.cart.index', compact('products', 'subtotal', 'schools', 'supplierFeeProfiles', 'hasEdukitItems', 'cartNotice'));
    }

    public function store(Request $request, Product $product, MarketplaceSourceService $sources): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $source = $sources->source($product, $request->integer('supplier_offer_id') ?: null);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.$source['quantity']],
            'supplier_offer_id' => ['nullable', 'integer'],
            'checkout' => ['nullable', 'in:1'],
        ]);
        $quantity = (int) ($data['quantity'] ?? 1);

        $cart = session('cart', []);
        $selectedOfferId = $source['offer']?->id;
        $previousOfferId = session('cart_sources.'.$product->id);
        $sameSource = (string) ($previousOfferId ?? '') === (string) ($selectedOfferId ?? '');
        $cart[$product->id] = min($source['quantity'], ($sameSource ? ($cart[$product->id] ?? 0) : 0) + $quantity);

        session(['cart' => $cart]);
        session(['cart_sources.'.$product->id => $selectedOfferId]);

        if (($data['checkout'] ?? null) === '1') {
            return redirect()->route('website.cart.index')->withFragment('order-details')
                ->with('status', "{$product->name} added. Enter your details to view the invoice.");
        }

        return back()->with('cart_added', "{$product->name} added to cart.");
    }

    public function update(Request $request, Product $product, MarketplaceSourceService $sources): RedirectResponse
    {
        $source = $sources->source($product, session('cart_sources.'.$product->id));
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$source['quantity']],
        ]);

        $cart = session('cart', []);
        $cart[$product->id] = (int) $data['quantity'];

        session(['cart' => $cart]);

        return back()->with('status', "{$product->name} quantity updated.");
    }

    public function submit(Request $request, SchoolDeliveryService $delivery): RedirectResponse
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return back()->withErrors(['cart' => 'Add at least one product before submitting your cart.']);
        }

        $data = $request->validate([
            'parent_name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ] + $delivery->validationRules());

        $data = $delivery->applyTo($data);

        $products = Product::query()
            ->active()
            ->with('approvedSupplierOffers.supplier')
            ->whereIn('id', array_keys($cart))
            ->get();

        if ($products->count() !== count($cart)) {
            return redirect()->route('website.cart.index')
                ->withErrors(['cart' => 'Some cart items are no longer available. Review your cart and try again.']);
        }

        $sourceService = app(MarketplaceSourceService::class);
        $items = $products->map(function (Product $product) use ($cart, $sourceService): array {
            $quantity = (int) ($cart[$product->id] ?? 0);
            $source = $sourceService->source($product, session('cart_sources.'.$product->id));
            $unitPrice = (int) $source['price'];

            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit_cost' => (int) ($source['offer']?->supplier_price ?? $product->cost_price),
                'unit_price' => $unitPrice,
                'fulfilment_source' => $source['type'],
                'supplier_offer_id' => $source['offer']?->id,
                'supplier_id' => $source['offer']?->supplier_id,
                'source_label' => $source['label'],
                'quantity' => $quantity,
                'line_total' => $unitPrice * $quantity,
            ];
        })->filter(fn (array $item): bool => $item['quantity'] > 0)->values();

        if ($items->isEmpty()) {
            return back()->withErrors(['cart' => 'The products in your cart are no longer available.']);
        }

        foreach ($items as $item) {
            $product = $products->firstWhere('id', $item['product_id']);

            $available = $item['fulfilment_source'] === 'supplier'
                ? (int) $product?->approvedSupplierOffers->firstWhere('id', $item['supplier_offer_id'])?->quantity_available
                : (int) $product?->stock_quantity;

            if (! $product || $available < $item['quantity']) {
                return back()->withErrors(['cart' => "{$item['name']} has only ".number_format($available).' available from the selected source.']);
            }
        }

        if ($items->contains(fn ($item) => $item['fulfilment_source'] === 'supplier') && $data['delivery_preference'] !== 'school') {
            return back()->withErrors(['delivery_preference' => 'Supplier-direct products must be delivered to a selected school.']);
        }

        $hasEdukitItems = $items->contains(fn ($item) => $item['fulfilment_source'] === 'edukit');
        $supplierIds = $items->where('fulfilment_source', 'supplier')->pluck('supplier_id')->unique();
        $destinationDistrict = \App\Models\District::find($data['district_id']);
        $supplierDeliveryFee = \App\Models\Supplier::whereIn('id', $supplierIds)->get()->sum(
            fn ($supplier) => strcasecmp((string) $supplier->district, (string) $destinationDistrict?->name) === 0
                ? $supplier->local_delivery_fee
                : $supplier->other_district_delivery_fee
        );
        $data['delivery_fee'] = ($hasEdukitItems ? (int) $data['delivery_fee'] : 0) + $supplierDeliveryFee;

        $itemsSubtotal = $items->sum('line_total');

        $shoppingList = DB::transaction(function () use ($data, $items, $itemsSubtotal): ShoppingList {
            $shoppingList = ShoppingList::create($data + [
                'source' => ShoppingList::SOURCE_CART,
                'reference' => ShoppingList::nextReference(),
                'cart_items' => $items->all(),
                'items_subtotal' => $itemsSubtotal,
                'estimated_total' => $itemsSubtotal + $data['delivery_fee'],
                'status' => ShoppingList::STATUS_QUOTED,
                'payment_status' => ShoppingList::PAYMENT_UNPAID,
            ]);

            $shoppingList->lineItems()->createMany($items->map(fn (array $item): array => [
                'product_id' => $item['product_id'],
                'supplier_offer_id' => $item['supplier_offer_id'],
                'supplier_id' => $item['supplier_id'],
                'fulfilment_source' => $item['fulfilment_source'],
                'product_name' => $item['name'],
                'sku' => $item['sku'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'unit_price' => $item['unit_price'],
                'line_total' => $item['line_total'],
                'cost_total' => 0,
                'profit_total' => 0,
            ])->all());

            return $shoppingList;
        });

        session()->forget('cart');
        session()->forget('cart_sources');
        InvoiceAccess::grant($shoppingList);

        return redirect()
            ->route('website.quote.show', $shoppingList->reference)
            ->with('status', 'Your invoice is ready. Review the items, convenience fee and total before paying.');
    }

    public function quote(string $reference): View
    {
        $shoppingList = ShoppingList::with('assignedDriver', 'school.district', 'lineItems')->where('reference', $reference)->firstOrFail();
        InvoiceAccess::check($shoppingList);

        return view('website.quote', compact('shoppingList'));
    }

    public function pay(Request $request, string $reference, InventoryService $inventory, SupplierSaleService $supplierSales, DemoPaymentService $demoPayments): RedirectResponse
    {
        $shoppingList = ShoppingList::where('reference', $reference)->firstOrFail();
        InvoiceAccess::check($shoppingList);

        abort_unless($shoppingList->status === ShoppingList::STATUS_QUOTED
            && $shoppingList->payment_status !== ShoppingList::PAYMENT_PAID
            && $shoppingList->estimated_total > 0, 404);

        $request->validate(['confirm_items' => ['accepted']]);

        if (PaymentMode::demoEnabled()) {
            $demoPayments->pay($shoppingList);

            return redirect()->route('website.quote.show', $shoppingList->reference)
                ->with('status', 'Demo payment completed. No money was collected.');
        }

        $inventory->ensureInvoiceHasDisplayStock($shoppingList);
        $supplierSales->ensureStock($shoppingList);

        $secretKey = config('services.flutterwave.secret_key');

        if (! $secretKey) {
            return back()->withErrors(['payment' => 'Flutterwave checkout is not connected yet. Add the Flutterwave secret key, then this invoice can be paid online.']);
        }

        $txRef = $shoppingList->reference.'-'.Str::upper(Str::random(12));
        $attempt = $shoppingList->paymentAttempts()->create([
            'tx_ref' => $txRef,
            'amount' => $shoppingList->estimated_total,
            'currency' => 'UGX',
            'status' => 'initiated',
        ]);
        try {
            $response = Http::withToken($secretKey)
                ->acceptJson()->timeout(20)
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
                    'description' => 'School supplies invoice including convenience fee.',
                ],
                'meta' => [
                    'shopping_list_id' => $shoppingList->id,
                    'reference' => $shoppingList->reference,
                ],
                ]);
        } catch (ConnectionException $exception) {
            report($exception);
            $attempt->update(['status' => 'failed']);
            return back()->withErrors(['payment' => 'Checkout is temporarily unavailable. Please try again.']);
        }

        $checkoutUrl = $response->json('data.link');

        if (! $response->successful() || ! $checkoutUrl) {
            $attempt->update(['status' => 'failed']);
            return back()->withErrors(['payment' => 'Flutterwave could not start checkout. Please try again or contact EduKit support.']);
        }

        $attempt->update(['checkout_url' => $checkoutUrl]);

        $shoppingList->update([
            'payment_status' => ShoppingList::PAYMENT_PENDING,
            'payment_provider' => 'flutterwave',
            'payment_reference' => $txRef,
        ]);

        return redirect()->away($checkoutUrl);
    }

    public function flutterwaveCallback(Request $request, FlutterwavePaymentService $payments): RedirectResponse
    {
        $txRef = (string) $request->query('tx_ref');
        $transactionId = (string) $request->query('transaction_id');

        abort_if($txRef === '', 404);

        $attempt = $payments->attempt($txRef);
        $shoppingList = $attempt->shoppingList;

        if ($request->query('status') !== 'successful' || $transactionId === '') {
            $payments->markFailed($attempt);
            return redirect()
                ->route('website.quote.show', $shoppingList->reference)
                ->withErrors(['payment' => 'The payment was not completed. You can try again from this invoice.']);
        }

        if (! $payments->verifyAndRecord($attempt, $transactionId)) {
            return redirect()
                ->route('website.quote.show', $shoppingList->reference)
                ->withErrors(['payment' => 'Flutterwave payment verification failed. Please contact EduKit support if money was deducted.']);
        }

        return redirect()
            ->route('website.quote.show', $shoppingList->reference)
            ->with('status', $shoppingList->fresh()->payment_exception
                ? 'Your payment update needs review. Please check the invoice status or contact EduKit support.'
                : 'Payment received. EduKit can now begin fulfilment.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session()->forget('cart_sources.'.$product->id);

        session(['cart' => $cart]);

        return back()->with('status', "{$product->name} removed from cart.");
    }

    private function customerEmail(ShoppingList $shoppingList): string
    {
        if ($shoppingList->email && filter_var($shoppingList->email, FILTER_VALIDATE_EMAIL)) {
            return $shoppingList->email;
        }

        return 'invoice-'.$shoppingList->id.'@edukit.local';
    }
}
